<?php

namespace App\Filament\Resources;

use App\Enums\KycVerificationStatus;
use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Ressource volontairement restrictive : `pin_hash`, `balance_xof`,
// `failed_pin_attempts`, `locked_until`, `kyc_status` ne sont JAMAIS des
// champs de formulaire éditables en libre, et il n'existe plus AUCUNE action
// admin permettant de modifier `balance_xof` directement (voir historique
// git : l'action « Ajuster le solde » a été retirée à la demande du produit
// — « personne ne doit pouvoir modifier le solde d'un compte »). Le solde
// n'évolue plus que via de vraies opérations (API mobile) ou via la
// résolution/inversion d'une transaction déjà existante (TransactionResource,
// désormais journalisée dans le log d'audit, voir LogsAdminActivity). Le
// statut KYC se change uniquement via KycDocumentResource (approuver/
// rejeter). La fiche client expose aussi son historique de transactions,
// ses cartes et ses sous-comptes (poches, devises) via des
// `RelationManagers` dédiés (voir `getRelations`).
//
// Actions de ligne volontairement limitées à Voir/Modifier (demande
// explicite du produit) : déverrouillage, blocage
// (`blockPermanently`/`blockTemporarily`/`unblock`), réinitialisation du
// code secret (`resetPin`, efface `pin_hash` — jamais un nouveau code
// choisi par l'admin, il ne le connaît jamais) et suppression vivent
// toutes sur `AccountActionsWidget`, en pied de la fiche client (voir
// `ViewAccount::getFooterWidgets()`). Le blocage est distinct du
// verrouillage automatique (que le client lève lui-même) et du statut KYC
// `suspended` (ne restreint que les mouvements d'argent côté app) : un
// blocage admin coupe tout accès à l'API, connexion et jetons déjà émis
// compris (voir `Account::isBlocked()`, `EnsureAccountIsNotBlocked`). La
// suppression reste bloquée tant que le client détient le moindre fonds
// (solde principal, carte, poche ou sous-compte devise) — supprimer un
// compte ne doit jamais faire disparaître de l'argent sans trace.
class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Comptes clients';

    protected static ?string $navigationGroup = 'Clients';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('phone_number')
                ->label('Téléphone')
                ->disabled(),
            Forms\Components\TextInput::make('first_name')->label('Prénom'),
            Forms\Components\TextInput::make('last_name')->label('Nom'),
            Forms\Components\TextInput::make('email')->email(),
            Forms\Components\Toggle::make('notifications_enabled')->label('Notifications activées'),
            Forms\Components\Select::make('preferred_locale')
                ->label('Langue préférée')
                ->options(['fr' => 'Français', 'en' => 'English']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Téléphone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nom')
                    ->state(fn (Account $record) => trim("{$record->first_name} {$record->last_name}") ?: '—')
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('kyc_status')
                    ->label('KYC')
                    ->badge()
                    ->color(fn (KycVerificationStatus $state) => match ($state) {
                        KycVerificationStatus::Verified => 'success',
                        KycVerificationStatus::Pending, KycVerificationStatus::Review => 'warning',
                        KycVerificationStatus::Rejected, KycVerificationStatus::Suspended => 'danger',
                        KycVerificationStatus::NotStarted => 'gray',
                    }),
                Tables\Columns\TextColumn::make('balance_xof')
                    ->label('Solde')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Verrouillé')
                    ->boolean()
                    ->state(fn (Account $record) => $record->isLocked()),
                Tables\Columns\TextColumn::make('block_status')
                    ->label('Blocage')
                    ->badge()
                    ->state(fn (Account $record) => match (true) {
                        $record->isBlockedPermanently() => 'Définitif',
                        $record->isBlockedTemporarily() => 'Jusqu\'au '.$record->blocked_until->format('d/m/Y H:i'),
                        default => null,
                    })
                    ->placeholder('—')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kyc_status')
                    ->label('Statut KYC')
                    ->options(array_combine(
                        array_map(fn ($c) => $c->value, KycVerificationStatus::cases()),
                        array_map(fn ($c) => $c->value, KycVerificationStatus::cases()),
                    )),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    // La page « Voir » utilisait par défaut le formulaire d'édition en
    // lecture seule (aucun `infolist()` défini) : ce vrai infolist reprend
    // ces mêmes informations (rien perdu) et ajoute le statut de la carte
    // éphémère — jamais le numéro/CVV (chiffrés, voir
    // `EphemeralCard::casts()`, règle 4 de CLAUDE.md), uniquement le
    // statut et le solde, pour que le support puisse répondre à « je ne
    // vois plus ma carte éphémère » sans jamais voir les données bancaires
    // elles-mêmes.
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Compte')
                ->columns(3)
                ->schema([
                    TextEntry::make('phone_number')->label('Téléphone'),
                    TextEntry::make('full_name')
                        ->label('Nom')
                        ->state(fn (Account $record) => trim("{$record->first_name} {$record->last_name}") ?: '—'),
                    TextEntry::make('email')->label('E-mail')->placeholder('—'),
                    TextEntry::make('kyc_status')->label('Statut KYC')->badge(),
                    TextEntry::make('balance_xof')
                        ->label('Solde')
                        ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F'),
                    TextEntry::make('created_at')->label('Inscrit le')->dateTime('d/m/Y'),
                    TextEntry::make('block_status')
                        ->label('Blocage')
                        ->badge()
                        ->color('danger')
                        ->state(fn (Account $record) => match (true) {
                            $record->isBlockedPermanently() => 'Définitif',
                            $record->isBlockedTemporarily() => 'Jusqu\'au '.$record->blocked_until->format('d/m/Y H:i'),
                            default => null,
                        })
                        ->placeholder('Aucun')
                        ->visible(fn (Account $record) => $record->isBlocked()),
                    TextEntry::make('block_reason')
                        ->label('Motif du blocage')
                        ->visible(fn (Account $record) => $record->isBlocked()),
                ]),
            Section::make('Carte éphémère (achat en ligne)')
                ->schema([
                    TextEntry::make('ephemeralCard.balance_xof')
                        ->label('Solde')
                        ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F'),
                    TextEntry::make('ephemeralCard.expiry')
                        ->label('Expire')
                        ->state(fn (Account $record) => $record->ephemeralCard === null
                            ? null
                            : sprintf('%02d/%d', $record->ephemeralCard->expiry_month, $record->ephemeralCard->expiry_year)),
                    TextEntry::make('ephemeralCard.created_at')
                        ->label('Générée le')
                        ->dateTime('d/m/Y H:i'),
                ])
                ->visible(fn (Account $record) => $record->ephemeralCard !== null),
            Section::make('Carte éphémère (achat en ligne)')
                ->schema([
                    TextEntry::make('noEphemeralCard')
                        ->label('')
                        ->state('Aucune carte éphémère active en ce moment.'),
                ])
                ->visible(fn (Account $record) => $record->ephemeralCard === null),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
            RelationManagers\CardsRelationManager::class,
            RelationManagers\PocketsRelationManager::class,
            RelationManagers\ForeignBalancesRelationManager::class,
            RelationManagers\BeneficiariesRelationManager::class,
            RelationManagers\BillAccountsRelationManager::class,
            RelationManagers\SessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'view' => Pages\ViewAccount::route('/{record}'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

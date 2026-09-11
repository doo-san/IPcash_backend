<?php

namespace App\Filament\Resources;

use App\Enums\KycVerificationStatus;
use App\Filament\Resources\KycDocumentResource\Pages;
use App\Models\Account;
use App\Models\KycDocument;
use Filament\Forms;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// File de revue KYC — le cœur du travail manuel de l'admin (voir la
// décision produit : revue 100% manuelle au démarrage, aucun prestataire
// OCR/face-match branché). Aucune édition libre du statut : seules les
// actions Approuver/Rejeter changent l'état, et elles répercutent toujours
// le résultat sur `accounts.kyc_status` (source lue par l'app mobile). Le
// nom déclaré à l'inscription (`account.full_name`) est volontairement mis
// en avant à côté du document : c'est la comparaison manuelle de base de
// toute vérification d'identité. Pas de section « analyse automatique » —
// `document_analysis`/`face_verification` sur le modèle ne sont jamais
// renseignés par le vrai parcours (le contrat `/kyc/documents` ne les
// transmet pas, ils ne servent qu'à l'écran du client) : les afficher
// aurait laissé croire à une analyse qui n'a jamais lieu côté admin.
class KycDocumentResource extends Resource
{
    protected static ?string $model = KycDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Revue KYC';

    protected static ?string $navigationGroup = 'Clients';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::whereIn('status', ['pending', 'review'])->count();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('account.phone_number')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nom déclaré')
                    ->state(fn (KycDocument $record) => $record->account->fullName() ?? '—')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas(
                        'account',
                        fn ($accountQuery) => $accountQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"),
                    )),
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type de document'),
                Tables\Columns\IconColumn::make('has_client_side_anomaly')
                    ->label('Anomalie signalée')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (KycVerificationStatus $state) => match ($state) {
                        KycVerificationStatus::Verified => 'success',
                        KycVerificationStatus::Pending, KycVerificationStatus::Review => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Revu par')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'review' => 'À revoir (anomalie)',
                        'verified' => 'Vérifié',
                        'rejected' => 'Rejeté',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (KycDocument $record) => in_array($record->status, [
                        KycVerificationStatus::Pending, KycVerificationStatus::Review,
                    ]))
                    ->requiresConfirmation()
                    ->action(fn (KycDocument $record) => static::decide($record, KycVerificationStatus::Verified)),
                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (KycDocument $record) => in_array($record->status, [
                        KycVerificationStatus::Pending, KycVerificationStatus::Review,
                    ]))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif du rejet')
                            ->required(),
                    ])
                    ->action(fn (KycDocument $record, array $data) => static::decide(
                        $record,
                        KycVerificationStatus::Rejected,
                        $data['reason'],
                    )),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Client')
                ->description('Nom déclaré à comparer visuellement au document ci-dessous — cœur de la vérification manuelle.')
                ->columns(3)
                ->schema([
                    TextEntry::make('account.phone_number')->label('Téléphone'),
                    TextEntry::make('account.full_name')
                        ->label('Nom déclaré à l\'inscription')
                        ->weight('bold')
                        ->state(fn (KycDocument $record) => $record->account->fullName() ?? '—'),
                    TextEntry::make('document_type')->label('Type de document'),
                    TextEntry::make('status')->label('Statut')->badge(),
                ]),
            Section::make('Documents fournis')
                ->description(
                    'Stockage disque "public" pour le moment — à déplacer sur un disque privé '.
                    'avec URLs signées avant la mise en production (données KYC sensibles).',
                )
                ->columns(3)
                ->schema([
                    ImageEntry::make('front_path')->label('Recto')->disk('public'),
                    ImageEntry::make('back_path')->label('Verso')->disk('public'),
                    ImageEntry::make('selfie_path')->label('Selfie')->disk('public'),
                ]),
            Section::make('Revue')
                ->columns(3)
                ->schema([
                    TextEntry::make('reviewer.name')->label('Revu par')->placeholder('—'),
                    TextEntry::make('reviewed_at')->label('Le')->dateTime('d/m/Y H:i')->placeholder('—'),
                    TextEntry::make('rejection_reason')->label('Motif de rejet')->placeholder('—'),
                ]),
        ]);
    }

    private static function decide(KycDocument $record, KycVerificationStatus $decision, ?string $reason = null): void
    {
        $record->status = $decision;
        $record->rejection_reason = $reason;
        $record->reviewed_by = auth()->id();
        $record->reviewed_at = now();
        $record->save();

        /** @var Account $account */
        $account = $record->account;
        $account->kyc_status = $decision;
        $account->kyc_rejection_reason = $reason;
        $account->save();

        Notification::make()
            ->title($decision === KycVerificationStatus::Verified ? 'KYC approuvé' : 'KYC rejeté')
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKycDocuments::route('/'),
            'view' => Pages\ViewKycDocument::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

<?php

namespace App\Filament\Resources;

use App\Enums\InsurancePolicyStatus;
use App\Filament\Resources\InsurancePolicyResource\Pages;
use App\Models\InsurancePolicy;
use App\Services\Insurance\InsurancePolicyDecisionException;
use App\Services\Insurance\InsurancePolicyService;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

// File des contrats d'assurance souscrits depuis l'app (payés, en attente).
// Valider émet l'attestation PDF (voir InsurancePolicyService) ; Rejeter
// rembourse intégralement le client. Les deux actions sont réservées à
// `super_admin`, comme toute décision qui engage de l'argent.
class InsurancePolicyResource extends Resource
{
    protected static ?string $model = InsurancePolicy::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Assurances';

    protected static ?string $modelLabel = 'contrat d\'assurance';

    protected static ?string $navigationGroup = 'Clients';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', InsurancePolicyStatus::Pending)->count();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('account.phone_number')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('insurance_type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('plate_number')->label('Immatriculation')->searchable(),
                Tables\Columns\TextColumn::make('duration_months')->label('Durée')->suffix(' mois'),
                Tables\Columns\TextColumn::make('price_xof')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (InsurancePolicyStatus $state) => match ($state) {
                        InsurancePolicyStatus::Active => 'success',
                        InsurancePolicyStatus::Pending => 'warning',
                        InsurancePolicyStatus::Rejected => 'danger',
                    }),
                Tables\Columns\TextColumn::make('policy_number')->label('N° de police')->placeholder('—')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Souscrit le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'active' => 'Actif',
                        'rejected' => 'Rejeté',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (InsurancePolicy $record) => auth()->user()->isSuperAdmin()
                        && $record->status === InsurancePolicyStatus::Pending)
                    ->requiresConfirmation()
                    ->modalDescription('Le contrat devient actif aujourd\'hui et l\'attestation PDF est émise au nom des mentions renseignées dans « Réglages de l\'application ».')
                    ->action(function (InsurancePolicy $record) {
                        $missing = InsurancePolicyService::missingSettings();
                        if ($missing !== []) {
                            Notification::make()
                                ->title('Mentions de l\'attestation incomplètes')
                                ->body('À renseigner dans Réglages de l\'application : '.implode(', ', $missing).'.')
                                ->danger()
                                ->send();

                            return;
                        }

                        static::decide(fn (InsurancePolicyService $service) => $service->approve($record, auth()->user()), 'Contrat validé, attestation émise');
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (InsurancePolicy $record) => auth()->user()->isSuperAdmin()
                        && $record->status === InsurancePolicyStatus::Pending)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif du rejet (visible par le client)')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->modalDescription('Le client est intégralement remboursé (prix + frais) sur son solde.')
                    ->action(fn (InsurancePolicy $record, array $data) => static::decide(
                        fn (InsurancePolicyService $service) => $service->reject($record, auth()->user(), $data['reason']),
                        'Contrat rejeté, client remboursé',
                    )),
                Tables\Actions\Action::make('downloadAttestation')
                    ->label('Attestation')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (InsurancePolicy $record) => $record->attestation_path !== null)
                    ->action(fn (InsurancePolicy $record) => Storage::disk('insurance')->download(
                        $record->attestation_path,
                        "attestation-{$record->policy_number}.pdf",
                    )),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Client')
                ->columns(3)
                ->schema([
                    TextEntry::make('account.phone_number')->label('Téléphone'),
                    TextEntry::make('insured')
                        ->label('Nom')
                        ->state(fn (InsurancePolicy $record) => $record->account->fullName() ?? '—'),
                    TextEntry::make('account.kyc_status')->label('KYC')->badge(),
                ]),
            Section::make('Contrat')
                ->columns(3)
                ->schema([
                    TextEntry::make('status')->label('Statut')->badge(),
                    TextEntry::make('insurance_type')->label('Type'),
                    TextEntry::make('duration_months')->label('Durée')->suffix(' mois'),
                    TextEntry::make('price_xof')->label('Prix')->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F'),
                    TextEntry::make('fee_xof')->label('Frais')->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F'),
                    TextEntry::make('policy_number')->label('N° de police')->placeholder('—'),
                    TextEntry::make('starts_at')->label('Début')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('ends_at')->label('Fin')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('rejection_reason')->label('Motif de rejet')->placeholder('—'),
                ]),
            Section::make('Véhicule (déclaratif)')
                ->columns(3)
                ->schema([
                    TextEntry::make('plate_number')->label('Immatriculation'),
                    TextEntry::make('vehicle_brand')->label('Marque')->placeholder('—'),
                    TextEntry::make('vehicle_usage')->label('Usage'),
                    TextEntry::make('horsepower')->label('Puissance (CV)')->placeholder('—'),
                    TextEntry::make('seats')->label('Places')->placeholder('—'),
                    TextEntry::make('age_bracket')->label('Âge')->placeholder('—'),
                    TextEntry::make('displacement_cc')->label('Cylindrée (cm³)')->placeholder('—'),
                    TextEntry::make('body_type')->label('Carrosserie')->placeholder('—'),
                ]),
            Section::make('Revue')
                ->columns(2)
                ->schema([
                    TextEntry::make('reviewer.name')->label('Traité par')->placeholder('—'),
                    TextEntry::make('reviewed_at')->label('Le')->dateTime('d/m/Y H:i')->placeholder('—'),
                ]),
        ]);
    }

    /** @param  callable(InsurancePolicyService): InsurancePolicy  $decision */
    private static function decide(callable $decision, string $successTitle): void
    {
        try {
            $decision(app(InsurancePolicyService::class));
        } catch (InsurancePolicyDecisionException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($successTitle)->success()->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInsurancePolicies::route('/'),
            'view' => Pages\ViewInsurancePolicy::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}

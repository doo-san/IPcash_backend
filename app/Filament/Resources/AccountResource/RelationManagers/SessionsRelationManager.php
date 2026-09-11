<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use App\Models\RefreshToken;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

// Sessions actives du client = ses `RefreshToken` émis à la connexion (un
// par appareil, voir `AuthController::issueTokens`) — directement sur sa
// fiche, plus de ressource autonome dans le menu de l'admin (demande
// produit : même traitement que bénéficiaires / comptes de facturation).
// Révoquer bloque le renouvellement futur ; l'appareil se déconnecte au
// plus tard à l'expiration du token d'accès en cours (15 min).
class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'refreshTokens';

    protected static ?string $title = 'Sessions actives';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('device_id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('device_id')->label('Appareil')->placeholder('—')->searchable(),
                Tables\Columns\IconColumn::make('is_revoked')
                    ->label('Révoquée')
                    ->boolean()
                    ->state(fn (RefreshToken $record) => $record->revoked_at !== null),
                Tables\Columns\TextColumn::make('created_at')->label('Connecté depuis')->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('expires_at')->label('Expire le')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('revoked_at')
                    ->label('Révoquée')
                    ->nullable(),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('revoke')
                    ->label('Révoquer')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (RefreshToken $record) => $record->revoked_at === null)
                    ->requiresConfirmation()
                    ->action(function (RefreshToken $record) {
                        $record->revoked_at = now();
                        $record->save();
                        Notification::make()->title('Session révoquée')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }
}

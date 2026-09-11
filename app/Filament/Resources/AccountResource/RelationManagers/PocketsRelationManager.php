<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use App\Filament\Resources\PocketResource;
use App\Models\Pocket;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

// Poches d'épargne du client, directement sur sa fiche — exigence
// produit : « avoir... les sous-comptes que le client possède » depuis
// « Comptes clients ». Lecture + déverrouillage seulement, comme
// `PocketResource` : jamais d'ajustement manuel du solde.
class PocketsRelationManager extends RelationManager
{
    protected static string $relationship = 'pockets';

    protected static ?string $title = "Poches d'épargne";

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nom'),
                Tables\Columns\TextColumn::make('balance_xof')
                    ->label('Solde')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F'),
                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Bloquée')
                    ->boolean()
                    ->state(fn (Pocket $record) => $record->isLocked()),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => PocketResource::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('unlock')
                    ->label('Débloquer')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->visible(fn (Pocket $record) => $record->isLocked())
                    ->requiresConfirmation()
                    ->action(function (Pocket $record) {
                        $record->locked_until = null;
                        $record->save();
                        Notification::make()->title('Poche débloquée')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }
}

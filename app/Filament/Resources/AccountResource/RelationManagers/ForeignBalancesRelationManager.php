<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use App\Filament\Resources\ForeignBalanceResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

// Sous-comptes devise IPchange du client, directement sur sa fiche —
// exigence produit : « avoir... les sous-comptes que le client possède »
// depuis « Comptes clients ». Lecture seule, comme `ForeignBalanceResource`.
class ForeignBalancesRelationManager extends RelationManager
{
    protected static string $relationship = 'foreignBalances';

    protected static ?string $title = 'Sous-comptes devise';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('currency_code')
            ->columns([
                Tables\Columns\TextColumn::make('currency_code')
                    ->label('Devise')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount_minor_units')
                    ->label('Solde (unité mineure)')
                    ->numeric(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => ForeignBalanceResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([]);
    }
}

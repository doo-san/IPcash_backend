<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Filament\Resources\TransactionResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

// Exigence produit : « avoir le suivi des transactions des clients » — un
// onglet directement sur la fiche client plutôt qu'obliger le staff à
// chercher son numéro dans la liste globale des transactions. Lecture
// seule ici (voir TransactionResource) ; « Voir » renvoie vers la fiche
// complète pour résoudre/inverser au besoin.
class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Transactions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (TransactionStatus $state) => match ($state) {
                        TransactionStatus::Completed => 'success',
                        TransactionStatus::Pending => 'warning',
                        TransactionStatus::Failed => 'danger',
                        TransactionStatus::Reversed => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount_xof')
                    ->label('Montant')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F')
                    ->color(fn (int $state) => $state >= 0 ? 'success' : 'danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(array_column(TransactionType::cases(), 'value', 'value')),
                Tables\Filters\SelectFilter::make('status')
                    ->options(array_column(TransactionStatus::cases(), 'value', 'value')),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => TransactionResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([]);
    }
}

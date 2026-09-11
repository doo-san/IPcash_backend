<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ForeignBalanceResource\Pages;
use App\Models\ForeignBalance;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Sous-comptes devise IPchange — solde réel en devise étrangère (exception
// isolée à la règle 1 de CLAUDE.md, voir `ForeignBalance` côté Flutter).
// Comme `TransactionResource` : lecture seule, jamais d'ajustement manuel
// sans passer par une vraie opération tracée.
class ForeignBalanceResource extends Resource
{
    protected static ?string $model = ForeignBalance::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Sous-comptes devise';

    protected static ?string $navigationGroup = 'Argent';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account.phone_number')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency_code')
                    ->label('Devise')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount_minor_units')
                    ->label('Solde (unité mineure)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency_code')
                    ->label('Devise')
                    ->options(fn () => ForeignBalance::query()
                        ->distinct()
                        ->pluck('currency_code', 'currency_code')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListForeignBalances::route('/'),
            'view' => Pages\ViewForeignBalance::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

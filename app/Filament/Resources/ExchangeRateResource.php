<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeRateResource\Pages;
use App\Models\ExchangeRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Modifier un taux ici a un effet réel et immédiat : l'app le lit via
// GET /exchange-rates (ForeignBalanceController::exchangeRates()) pour
// l'aperçu de conversion IPchange, et convertFromXof()/convertToXof()
// appliquent ce même taux pour de vrai côté `ForeignBalance` — seule
// exception à la règle 1 de CLAUDE.md (solde réel en devise étrangère).
// Ailleurs dans l'app, `DisplayCurrency` reste un taux fixe purement
// indicatif, sans lien avec cette table.
class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Taux de change';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\TextInput::make('currency_code')
                ->label('Code devise (ISO)')
                ->placeholder('EUR')
                ->maxLength(3)
                ->required()
                ->unique(ignoreRecord: true)
                ->disabled(fn (string $operation) => $operation === 'edit'),
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),
            Forms\Components\TextInput::make('flag')
                ->label('Drapeau (emoji)'),
            Forms\Components\TextInput::make('rate_to_xof')
                ->label('Taux vers XOF (1 unité = X XOF)')
                ->numeric()
                ->step('0.0001')
                ->required(),
            Forms\Components\Toggle::make('is_pegged_to_xof')
                ->label('Arrimée au XOF (parité fixe)')
                ->helperText('Vrai pour le XOF lui-même et le XAF — aucune conversion à afficher.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('currency_code')->label('Code'),
                Tables\Columns\TextColumn::make('flag')->label(''),
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
                Tables\Columns\TextColumn::make('rate_to_xof')
                    ->label('Taux vers XOF')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_pegged_to_xof')
                    ->label('Arrimée')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExchangeRates::route('/'),
            'create' => Pages\CreateExchangeRate::route('/create'),
            'edit' => Pages\EditExchangeRate::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Enums\FeeScope;
use App\Enums\FeeType;
use App\Filament\Resources\FeeRuleResource\Pages;
use App\Models\FeeRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Remplace le taux de transfert P2P codé en dur côté Flutter (0,5 %) —
// paramètre le plus sensible du produit, voir la migration pour le détail.
class FeeRuleResource extends Resource
{
    protected static ?string $model = FeeRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Frais';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\Select::make('scope')
                ->label('Opération')
                ->options(collect(FeeScope::cases())
                    ->mapWithKeys(fn (FeeScope $c) => [$c->value => $c->label()])
                    ->all())
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\Select::make('type')
                ->label('Type')
                ->options(['percent' => 'Pourcentage', 'fixed' => 'Montant fixe'])
                ->required()
                ->live(),
            Forms\Components\TextInput::make('value')
                ->label(fn (Forms\Get $get) => $get('type') === 'percent'
                    ? 'Taux (points de base — 50 = 0,5 %)'
                    : 'Montant (XOF)')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('min_fee_xof')
                ->label('Frais minimum (XOF)')
                ->numeric(),
            Forms\Components\TextInput::make('max_fee_xof')
                ->label('Frais maximum (XOF)')
                ->numeric(),
            Forms\Components\Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('scope')
                    ->label('Opération')
                    ->badge()
                    ->formatStateUsing(fn (FeeScope $state) => $state->label())
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Type'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valeur')
                    ->formatStateUsing(fn (FeeRule $record) => $record->type === FeeType::Percent
                        ? number_format($record->value / 100, 2).' %'
                        : number_format($record->value, 0, ',', ' ').' F'),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
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
            'index' => Pages\ListFeeRules::route('/'),
            'create' => Pages\CreateFeeRule::route('/create'),
            'edit' => Pages\EditFeeRule::route('/{record}/edit'),
        ];
    }
}

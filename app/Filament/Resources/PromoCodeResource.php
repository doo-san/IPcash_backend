<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Donne enfin un catalogue réel au champ `promoCode` déjà présent côté
// Flutter (écran IPchange) mais qui ne menait nulle part.
class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Codes promo';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\TextInput::make('code')
                ->label('Code')
                ->required()
                ->unique(ignoreRecord: true)
                ->disabled(fn (string $operation) => $operation === 'edit'),
            Forms\Components\Select::make('discount_type')
                ->label('Type de remise')
                ->options(['percent' => 'Pourcentage', 'fixed' => 'Montant fixe'])
                ->required(),
            Forms\Components\TextInput::make('discount_value')
                ->label('Valeur (points de base ou XOF)')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('max_redemptions')
                ->label('Utilisations maximum')
                ->helperText('Vide = illimité.')
                ->numeric(),
            Forms\Components\DateTimePicker::make('valid_from')
                ->label('Valide à partir de'),
            Forms\Components\DateTimePicker::make('valid_until')
                ->label('Valide jusqu\'au'),
            Forms\Components\Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Code')->searchable(),
                Tables\Columns\TextColumn::make('discount_type')->label('Type'),
                Tables\Columns\TextColumn::make('discount_value')->label('Valeur'),
                Tables\Columns\TextColumn::make('redemptions_count')
                    ->label('Utilisé')
                    ->formatStateUsing(fn (PromoCode $record) => $record->max_redemptions
                        ? "{$record->redemptions_count} / {$record->max_redemptions}"
                        : (string) $record->redemptions_count),
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
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}

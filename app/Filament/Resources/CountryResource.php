<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CountryResource\Pages;
use App\Models\Country;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Miroir de `PhoneCountry` côté Flutter — pays pris en charge pour la
// saisie de numéro (inscription, transfert, filtrage mobile money…).
class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Pays';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\TextInput::make('dial_code')
                ->label('Indicatif')
                ->placeholder('+221')
                ->required()
                ->unique(ignoreRecord: true)
                ->disabled(fn (string $operation) => $operation === 'edit'),
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),
            Forms\Components\TextInput::make('flag')
                ->label('Drapeau (emoji)')
                ->required(),
            Forms\Components\TextInput::make('currency_code')
                ->label('Devise')
                ->maxLength(3)
                ->required(),
            Forms\Components\TextInput::make('min_digits')
                ->label('Chiffres minimum')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('max_digits')
                ->label('Chiffres maximum')
                ->numeric()
                ->required(),
            Forms\Components\TagsInput::make('mobile_prefixes')
                ->label('Préfixes mobiles')
                ->helperText('Vide = non vérifié (seule la longueur du numéro est contrôlée).')
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('flag')->label(''),
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
                Tables\Columns\TextColumn::make('dial_code')->label('Indicatif'),
                Tables\Columns\TextColumn::make('currency_code')->label('Devise'),
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
            'index' => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'edit' => Pages\EditCountry::route('/{record}/edit'),
        ];
    }
}

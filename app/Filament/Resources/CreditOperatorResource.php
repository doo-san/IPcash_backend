<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CreditOperatorResource\Pages;
use App\Filament\Support\ApiCredentialsFormSection;
use App\Models\CreditOperator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Miroir de `CreditOperator` côté Flutter (achat de crédit télécom).
class CreditOperatorResource extends Resource
{
    protected static ?string $model = CreditOperator::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationLabel = 'Opérateurs crédit';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),
            Forms\Components\FileUpload::make('logo_url')
                ->label('Logo')
                ->image()
                ->directory('logos/credit-operators')
                ->imagePreviewHeight('80'),
            Forms\Components\Toggle::make('is_available')
                ->label('Disponible')
                ->helperText('Listé pour information mais non proposé si désactivé (ex. accord commercial manquant).')
                ->default(true),
            ApiCredentialsFormSection::make(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_url')->label('Logo')->circular(),
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
                Tables\Columns\IconColumn::make('is_available')->label('Disponible')->boolean(),
                Tables\Columns\IconColumn::make('is_configured')
                    ->label('API configurée')
                    ->boolean()
                    ->state(fn (CreditOperator $record) => $record->isConfigured()),
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
            'index' => Pages\ListCreditOperators::route('/'),
            'create' => Pages\CreateCreditOperator::route('/create'),
            'edit' => Pages\EditCreditOperator::route('/{record}/edit'),
        ];
    }
}

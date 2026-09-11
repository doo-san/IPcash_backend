<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MobileMoneyProviderResource\Pages;
use App\Filament\Support\ApiCredentialsFormSection;
use App\Models\MobileMoneyProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Référentiel géré depuis l'admin — pas de piste d'audit nécessaire ici
// (données de configuration, pas d'argent réel) : formulaire/CRUD complet
// à la différence des autres ressources.
class MobileMoneyProviderResource extends Resource
{
    protected static ?string $model = MobileMoneyProvider::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationLabel = 'Opérateurs mobile money';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),
            Forms\Components\FileUpload::make('logo_url')
                ->label('Logo')
                ->image()
                ->directory('logos/mobile-money')
                ->imagePreviewHeight('80'),
            Forms\Components\TextInput::make('min_amount_xof')
                ->label('Montant minimum (XOF)')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('max_amount_xof')
                ->label('Montant maximum (XOF)')
                ->numeric()
                ->required(),
            Forms\Components\Select::make('flow')
                ->label('Flux')
                ->options(['redirect' => 'Redirection', 'ussd' => 'USSD'])
                ->required(),
            Forms\Components\TextInput::make('country_dial_code')
                ->label('Indicatif pays')
                ->placeholder('+221')
                ->required(),
            Forms\Components\TextInput::make('currency_code')
                ->label('Devise')
                ->placeholder('XOF')
                ->maxLength(3)
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
            ApiCredentialsFormSection::make()->columnSpanFull(),
            Forms\Components\TextInput::make('merchant_code')
                ->label('Code marchand')
                ->helperText(
                    'Requis par certains prestataires en plus de la clé/secret API '.
                    '(ex. « Mercode » Orange Money OM Pay).',
                )
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_url')->label('Logo')->circular(),
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
                Tables\Columns\TextColumn::make('flow')->label('Flux')->badge(),
                Tables\Columns\TextColumn::make('country_dial_code')->label('Pays'),
                Tables\Columns\TextColumn::make('currency_code')->label('Devise'),
                Tables\Columns\TextColumn::make('min_amount_xof')
                    ->label('Min')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F'),
                Tables\Columns\TextColumn::make('max_amount_xof')
                    ->label('Max')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F'),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
                Tables\Columns\IconColumn::make('is_configured')
                    ->label('API configurée')
                    ->boolean()
                    ->state(fn (MobileMoneyProvider $record) => $record->isConfigured()),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Actif'),
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
            'index' => Pages\ListMobileMoneyProviders::route('/'),
            'create' => Pages\CreateMobileMoneyProvider::route('/create'),
            'edit' => Pages\EditMobileMoneyProvider::route('/{record}/edit'),
        ];
    }
}

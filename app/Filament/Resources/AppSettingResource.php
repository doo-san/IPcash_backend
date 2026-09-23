<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppSettingResource\Pages;
use App\Models\AppSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Réglages clé/valeur pour ce qui ne mérite pas sa propre table — pour
// commencer, le numéro de support (bouton d'appel de `profile_screen.dart`
// côté app, `_fallbackSupportPhoneNumber` en repli si la ligne n'existe pas
// encore ici).
class AppSettingResource extends Resource
{
    protected static ?string $model = AppSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Réglages';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('key')
                ->label('Clé')
                ->required()
                ->unique(ignoreRecord: true)
                ->disabled(fn (string $operation) => $operation === 'edit'),
            Forms\Components\Textarea::make('value')
                ->label('Valeur')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('description')
                ->label('Description'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')->label('Clé')->searchable(),
                Tables\Columns\TextColumn::make('value')->label('Valeur')->limit(60),
                Tables\Columns\TextColumn::make('description')->label('Description'),
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
            'index' => Pages\ListAppSettings::route('/'),
            'create' => Pages\CreateAppSetting::route('/create'),
            'edit' => Pages\EditAppSetting::route('/{record}/edit'),
        ];
    }
}

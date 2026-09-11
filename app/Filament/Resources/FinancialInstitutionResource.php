<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinancialInstitutionResource\Pages;
use App\Models\FinancialInstitution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Répertoire banques + IMF de `bank_link_screen.dart` — la liaison
// bancaire réelle reste non intégrée (CLAUDE.md règle 9), ce référentiel
// alimente seulement la liste affichée.
class FinancialInstitutionResource extends Resource
{
    protected static ?string $model = FinancialInstitution::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Banques & IMF';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),
            Forms\Components\Select::make('type')
                ->label('Type')
                ->options(['bank' => 'Banque', 'microfinance' => 'Microfinance'])
                ->required(),
            Forms\Components\FileUpload::make('logo_url')
                ->label('Logo')
                ->image()
                ->directory('logos/financial-institutions')
                ->imagePreviewHeight('80')
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
                Tables\Columns\ImageColumn::make('logo_url')->label('Logo')->circular(),
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Type')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['bank' => 'Banque', 'microfinance' => 'Microfinance']),
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
            'index' => Pages\ListFinancialInstitutions::route('/'),
            'create' => Pages\CreateFinancialInstitution::route('/create'),
            'edit' => Pages\EditFinancialInstitution::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Enums\BillProviderType;
use App\Filament\Resources\BillProviderResource\Pages;
use App\Filament\Resources\BillProviderResource\RelationManagers;
use App\Filament\Support\ApiCredentialsFormSection;
use App\Models\BillProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Miroir de `BillProviderType` côté Flutter (paiement de factures).
class BillProviderResource extends Resource
{
    protected static ?string $model = BillProvider::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Factures';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\Select::make('type')
                ->label('Type')
                ->options(array_column(BillProviderType::cases(), 'value', 'value'))
                ->required()
                ->unique(ignoreRecord: true)
                ->disabled(fn (string $operation) => $operation === 'edit'),
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),
            Forms\Components\FileUpload::make('logo_url')
                ->label('Logo')
                ->image()
                ->directory('logos/bill-providers')
                ->imagePreviewHeight('80')
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
            ApiCredentialsFormSection::make()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_url')->label('Logo')->circular(),
                Tables\Columns\TextColumn::make('type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
                Tables\Columns\IconColumn::make('is_configured')
                    ->label('API configurée')
                    ->boolean()
                    ->state(fn (BillProvider $record) => $record->isConfigured()),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\PlansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBillProviders::route('/'),
            'create' => Pages\CreateBillProvider::route('/create'),
            'edit' => Pages\EditBillProvider::route('/{record}/edit'),
        ];
    }
}

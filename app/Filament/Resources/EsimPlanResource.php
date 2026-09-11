<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EsimPlanResource\Pages;
use App\Models\EsimPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Miroir de `_EsimScope`/`_EsimData` côté Flutter (`esim_plan_screen.dart`)
// — pas encore branché à l'app (elle calcule toujours ses prix en dur, il
// manque un endpoint `/esim/plans` et le passage côté client à une source
// dynamique). Prix final directement éditable par combinaison scope ×
// palier de données, plutôt qu'un multiplicateur abstrait.
class EsimPlanResource extends Resource
{
    protected static ?string $model = EsimPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Forfaits eSIM';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\Select::make('scope')
                ->label('Zone')
                ->options(['local' => 'Local', 'regional' => 'Régional', 'global' => 'Mondial'])
                ->required(),
            Forms\Components\TextInput::make('data_gb')
                ->label('Données (Go)')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('validity_days')
                ->label('Validité (jours)')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('price_xof')
                ->label('Prix (XOF)')
                ->numeric()
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('scope')
            ->columns([
                Tables\Columns\TextColumn::make('scope')->label('Zone')->badge(),
                Tables\Columns\TextColumn::make('data_gb')
                    ->label('Données')
                    ->formatStateUsing(fn (int $state) => "{$state} Go")
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('validity_days')
                    ->label('Validité')
                    ->formatStateUsing(fn (int $state) => "{$state} j")
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_xof')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F')
                    ->sortable()
                    ->searchable(),
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
            'index' => Pages\ListEsimPlans::route('/'),
            'create' => Pages\CreateEsimPlan::route('/create'),
            'edit' => Pages\EditEsimPlan::route('/{record}/edit'),
        ];
    }
}

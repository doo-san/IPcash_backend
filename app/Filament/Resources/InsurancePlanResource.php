<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InsurancePlanResource\Pages;
use App\Models\InsurancePlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Miroir de `InsuranceType` + les durées de `InsuranceDurationScreen` côté
// Flutter — l'achat y renvoie toujours `Failure.integrationPending()`
// (aucun assureur réel intégré, CLAUDE.md règle 9) et utilise `Money.zero`
// en attendant justement ce choix de forfait. Pas encore branché à l'app.
class InsurancePlanResource extends Resource
{
    protected static ?string $model = InsurancePlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Forfaits assurance';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\Select::make('insurance_type')
                ->label('Type')
                ->options(['auto' => 'Auto', 'moto' => 'Moto'])
                ->required(),
            Forms\Components\Select::make('duration_months')
                ->label('Durée')
                ->options([3 => '3 mois', 6 => '6 mois', 12 => '12 mois'])
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
            ->defaultSort('insurance_type')
            ->columns([
                Tables\Columns\TextColumn::make('insurance_type')->label('Type')->badge()->searchable(),
                Tables\Columns\TextColumn::make('duration_months')
                    ->label('Durée')
                    ->formatStateUsing(fn (int $state) => "{$state} mois")
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_xof')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('insurance_type')
                    ->options(['auto' => 'Auto', 'moto' => 'Moto']),
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
            'index' => Pages\ListInsurancePlans::route('/'),
            'create' => Pages\CreateInsurancePlan::route('/create'),
            'edit' => Pages\EditInsurancePlan::route('/{record}/edit'),
        ];
    }
}

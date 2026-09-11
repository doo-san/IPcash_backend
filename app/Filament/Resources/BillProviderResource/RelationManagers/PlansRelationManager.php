<?php

namespace App\Filament\Resources\BillProviderResource\RelationManagers;

use App\Models\BillProviderPlan;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

// Forfaits (formules/options) de ce fournisseur de factures, directement
// sur sa fiche — demande produit : « forfaits factures et fournisseurs de
// factures doivent être sur la même page ». Contrairement aux autres
// RelationManagers de ce projet (cartes, poches…, lecture/actions
// simples), celui-ci a besoin de la création/édition complète — toujours
// via des actions personnalisées plutôt que Create/Edit/DeleteAction :
// Filament les masque silencieusement dans un RelationManager tant
// qu'aucune Policy Laravel n'existe pour le modèle (aucune n'existe dans
// ce projet), piège déjà rencontré sur les cartes/bénéficiaires d'un
// client.
class PlansRelationManager extends RelationManager
{
    protected static string $relationship = 'plans';

    protected static ?string $title = 'Forfaits';

    protected function planForm(): array
    {
        return [
            Forms\Components\Select::make('kind')
                ->label('Nature')
                ->options(['formula' => 'Formule', 'option' => 'Option'])
                ->required(),
            Forms\Components\TextInput::make('code')
                ->label('Code')
                ->helperText('Identifiant technique, ex. "access", "cineSeries".')
                ->required(),
            Forms\Components\TextInput::make('label')
                ->label('Libellé affiché')
                ->required(),
            Forms\Components\TextInput::make('price_xof')
                ->label('Prix (XOF)')
                ->numeric()
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('kind')->label('Nature'),
                Tables\Columns\TextColumn::make('code')->label('Code')->searchable(),
                Tables\Columns\TextColumn::make('label')->label('Libellé')->searchable(),
                Tables\Columns\TextColumn::make('price_xof')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('Ajouter un forfait')
                    ->icon('heroicon-o-plus')
                    ->form($this->planForm())
                    ->action(function (array $data) {
                        $this->getOwnerRecord()->plans()->create($data);
                        Notification::make()->title('Forfait créé')->success()->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Modifier')
                    ->icon('heroicon-o-pencil')
                    ->form($this->planForm())
                    ->fillForm(fn (BillProviderPlan $record) => $record->only(['kind', 'code', 'label', 'price_xof', 'is_active']))
                    ->action(function (BillProviderPlan $record, array $data) {
                        $record->update($data);
                        Notification::make()->title('Forfait mis à jour')->success()->send();
                    }),
                Tables\Actions\Action::make('delete')
                    ->label('Supprimer')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (BillProviderPlan $record) {
                        $record->delete();
                        Notification::make()->title('Forfait supprimé')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }
}

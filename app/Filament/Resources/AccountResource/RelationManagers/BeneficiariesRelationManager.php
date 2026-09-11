<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use App\Models\Beneficiary;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

// Bénéficiaires enregistrés par le client, directement sur sa fiche —
// demande produit explicite : uniquement ici, jamais comme ressource de
// premier niveau dans le menu de l'admin (contrairement à cartes/poches/
// sous-comptes, qui gardent tous les deux — une liste globale ET cet
// onglet). Un ancien `BeneficiaryResource` autonome a existé un temps et
// a été retiré pour cette raison.
class BeneficiariesRelationManager extends RelationManager
{
    protected static string $relationship = 'beneficiaries';

    protected static ?string $title = 'Bénéficiaires';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
                Tables\Columns\TextColumn::make('phone_number')->label('Téléphone')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Ajouté le')->dateTime('d/m/Y'),
            ])
            ->headerActions([])
            ->actions([
                // `Tables\Actions\DeleteAction` plutôt qu'une action
                // personnalisée serait plus court, mais Filament la masque
                // silencieusement dans un RelationManager tant qu'aucune
                // Policy Laravel n'existe pour le modèle (aucune n'existe
                // dans ce projet) — voir le même commentaire sur
                // `CardsRelationManager`.
                Tables\Actions\Action::make('delete')
                    ->label('Supprimer')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Beneficiary $record) {
                        $record->delete();
                        Notification::make()->title('Bénéficiaire supprimé')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }
}

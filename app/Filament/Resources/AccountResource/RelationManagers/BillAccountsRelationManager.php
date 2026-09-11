<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use App\Models\BillAccount;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

// Comptes de facturation enregistrés par le client (compteur Woyofal,
// abonnement Canal+…), directement sur sa fiche — demande produit :
// même traitement que les bénéficiaires, uniquement ici, plus de
// ressource autonome dans le menu de l'admin.
class BillAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'billAccounts';

    protected static ?string $title = 'Comptes de facturation';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nickname')
            ->columns([
                Tables\Columns\TextColumn::make('bill_provider_type')->label('Fournisseur')->badge(),
                Tables\Columns\TextColumn::make('nickname')->label('Surnom')->searchable(),
                Tables\Columns\TextColumn::make('account_number')->label('Numéro de compte')->searchable(),
            ])
            ->headerActions([])
            ->actions([
                // Voir le commentaire équivalent sur
                // `CardsRelationManager`/`BeneficiariesRelationManager` :
                // `DeleteAction` intégrée serait masquée silencieusement
                // sans Policy Laravel (aucune n'existe dans ce projet).
                Tables\Actions\Action::make('delete')
                    ->label('Supprimer')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (BillAccount $record) {
                        $record->delete();
                        Notification::make()->title('Compte de facturation supprimé')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }
}

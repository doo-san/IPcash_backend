<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Filament\Resources\AccountResource\Widgets\AccountActionsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    // Rendu après l'infolist et tous les onglets de RelationManagers
    // (Transactions en premier, voir AccountResource::getRelations()) —
    // demande explicite : les boutons de blocage/réinitialisation doivent
    // apparaître « en bas [de la fiche client], après la partie
    // transaction », pas comme actions de ligne sur la liste des comptes.
    protected function getFooterWidgets(): array
    {
        return [
            AccountActionsWidget::class,
        ];
    }
}

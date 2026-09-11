<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use Filament\Resources\Pages\EditRecord;

// Pas de DeleteAction : un compte client n'est jamais supprimé en dur
// (historique de transactions/KYC à conserver pour la conformité).
class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;
}

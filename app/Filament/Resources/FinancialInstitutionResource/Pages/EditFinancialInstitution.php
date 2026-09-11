<?php

namespace App\Filament\Resources\FinancialInstitutionResource\Pages;

use App\Filament\Resources\FinancialInstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinancialInstitution extends EditRecord
{
    protected static string $resource = FinancialInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

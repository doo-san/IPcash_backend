<?php

namespace App\Filament\Resources\CreditOperatorResource\Pages;

use App\Filament\Resources\CreditOperatorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCreditOperator extends EditRecord
{
    protected static string $resource = CreditOperatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

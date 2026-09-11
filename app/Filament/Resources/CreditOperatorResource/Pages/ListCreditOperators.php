<?php

namespace App\Filament\Resources\CreditOperatorResource\Pages;

use App\Filament\Resources\CreditOperatorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCreditOperators extends ListRecords
{
    protected static string $resource = CreditOperatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

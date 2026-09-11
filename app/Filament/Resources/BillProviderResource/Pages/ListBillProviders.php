<?php

namespace App\Filament\Resources\BillProviderResource\Pages;

use App\Filament\Resources\BillProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBillProviders extends ListRecords
{
    protected static string $resource = BillProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

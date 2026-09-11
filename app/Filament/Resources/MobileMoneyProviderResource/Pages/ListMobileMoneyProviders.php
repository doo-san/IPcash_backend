<?php

namespace App\Filament\Resources\MobileMoneyProviderResource\Pages;

use App\Filament\Resources\MobileMoneyProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMobileMoneyProviders extends ListRecords
{
    protected static string $resource = MobileMoneyProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

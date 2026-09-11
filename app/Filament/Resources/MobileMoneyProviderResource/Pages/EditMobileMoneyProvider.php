<?php

namespace App\Filament\Resources\MobileMoneyProviderResource\Pages;

use App\Filament\Resources\MobileMoneyProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMobileMoneyProvider extends EditRecord
{
    protected static string $resource = MobileMoneyProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

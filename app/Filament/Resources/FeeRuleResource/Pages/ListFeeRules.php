<?php

namespace App\Filament\Resources\FeeRuleResource\Pages;

use App\Filament\Resources\FeeRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeeRules extends ListRecords
{
    protected static string $resource = FeeRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\EsimPlanResource\Pages;

use App\Filament\Resources\EsimPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEsimPlan extends EditRecord
{
    protected static string $resource = EsimPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

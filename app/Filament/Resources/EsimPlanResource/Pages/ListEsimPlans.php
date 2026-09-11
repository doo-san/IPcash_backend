<?php

namespace App\Filament\Resources\EsimPlanResource\Pages;

use App\Filament\Resources\EsimPlanResource;
use App\Models\EsimPlan;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListEsimPlans extends ListRecords
{
    protected static string $resource = EsimPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // Trois onglets plutôt qu'un filtre déroulant — demande produit, plus
    // lisible pour naviguer entre les zones (local/régional/mondial) que
    // le `SelectFilter` qu'ils remplacent (retiré de `EsimPlanResource`).
    public function getTabs(): array
    {
        return [
            'local' => Tab::make('Local')
                ->query(fn ($query) => $query->where('scope', 'local'))
                ->badge(EsimPlan::where('scope', 'local')->count()),
            'regional' => Tab::make('Régional')
                ->query(fn ($query) => $query->where('scope', 'regional'))
                ->badge(EsimPlan::where('scope', 'regional')->count()),
            'global' => Tab::make('Mondial')
                ->query(fn ($query) => $query->where('scope', 'global'))
                ->badge(EsimPlan::where('scope', 'global')->count()),
        ];
    }
}

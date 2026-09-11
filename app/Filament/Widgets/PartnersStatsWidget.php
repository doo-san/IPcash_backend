<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BillProviderResource;
use App\Filament\Resources\CreditOperatorResource;
use App\Filament\Resources\FinancialInstitutionResource;
use App\Filament\Resources\MerchantResource;
use App\Filament\Resources\MobileMoneyProviderResource;
use App\Models\BillProvider;
use App\Models\CreditOperator;
use App\Models\FinancialInstitution;
use App\Models\Merchant;
use App\Models\MobileMoneyProvider;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

// Partenaires intégrés au catalogue admin (demande produit : « les
// partenaires » sur le tableau de bord) — nombre de fiches actives par
// catégorie, sans présager d'une intégration API réellement branchée
// (voir `isConfigured()` sur chaque modèle, propre à chaque ressource).
// Chaque carte pointe vers la page admin correspondante (`->url()`).
class PartnersStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        return [
            Stat::make('Opérateurs Mobile Money', MobileMoneyProvider::where('is_active', true)->count())
                ->description(MobileMoneyProvider::count().' au total')
                ->icon('heroicon-o-device-phone-mobile')
                ->url(MobileMoneyProviderResource::getUrl('index')),
            Stat::make('Fournisseurs de factures', BillProvider::where('is_active', true)->count())
                ->description(BillProvider::count().' au total')
                ->icon('heroicon-o-document-text')
                ->url(BillProviderResource::getUrl('index')),
            Stat::make('Banques & IMF', FinancialInstitution::where('is_active', true)->count())
                ->description(FinancialInstitution::count().' au total')
                ->icon('heroicon-o-building-library')
                ->url(FinancialInstitutionResource::getUrl('index')),
            Stat::make('Opérateurs crédit', CreditOperator::where('is_available', true)->count())
                ->description(CreditOperator::count().' au total')
                ->icon('heroicon-o-signal')
                ->url(CreditOperatorResource::getUrl('index')),
            Stat::make('Marchands', Merchant::count())
                ->icon('heroicon-o-building-storefront')
                ->url(MerchantResource::getUrl('index')),
        ];
    }
}

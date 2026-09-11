<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Http\Resources\FinancialInstitutionResource;
use App\Http\Resources\MobileMoneyProviderResource;
use App\Models\Country;
use App\Models\FinancialInstitution;
use App\Models\MobileMoneyProvider;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// Implémente le tag `providers` de openapi.yaml.
class ProviderController extends Controller
{
    public function mobileMoney(): AnonymousResourceCollection
    {
        return MobileMoneyProviderResource::collection(
            MobileMoneyProvider::where('is_active', true)->get(),
        );
    }

    public function financialInstitutions(): AnonymousResourceCollection
    {
        return FinancialInstitutionResource::collection(
            FinancialInstitution::where('is_active', true)->get(),
        );
    }

    // Miroir de `CountryResource` côté admin (voir sa migration) — jusqu'ici
    // sans aucun effet réel, `PhoneCountry` restant codé en dur côté app.
    public function countries(): AnonymousResourceCollection
    {
        return CountryResource::collection(
            Country::where('is_active', true)->orderBy('name')->get(),
        );
    }
}

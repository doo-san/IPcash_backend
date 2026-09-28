<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AppConfig;
use Illuminate\Http\JsonResponse;

// Implémente `GET /config` de openapi.yaml : réglages publics de l'app
// (maintenance, version minimale, limites, services ouverts), lus avant même
// la connexion. Édités depuis l'admin (AppConfigPage).
class ConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(AppConfig::publicPayload());
    }
}

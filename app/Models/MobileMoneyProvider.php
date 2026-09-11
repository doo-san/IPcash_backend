<?php

namespace App\Models;

use App\Enums\MobileMoneyFlow;
use App\Models\Concerns\HasLogo;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MobileMoneyProvider extends Model
{
    use HasLogo, HasUuids, LogsAdminActivity;

    protected $attributes = ['is_active' => true, 'is_live' => false];

    protected $fillable = [
        'name',
        'logo_url',
        'min_amount_xof',
        'max_amount_xof',
        'flow',
        'country_dial_code',
        'currency_code',
        'is_active',
        'api_base_url',
        'api_key',
        'api_secret',
        'merchant_code',
        'is_live',
    ];

    // `pin_hash`-like : jamais renvoyé en clair une fois soumis (voir
    // aussi CLAUDE.md règle 4). Le formulaire admin masque déjà la valeur
    // à l'affichage (champ mot de passe) ; `$hidden` protège en plus toute
    // sérialisation accidentelle (API, export…).
    protected $hidden = ['api_key', 'api_secret'];

    protected function casts(): array
    {
        return [
            'flow' => MobileMoneyFlow::class,
            'min_amount_xof' => 'integer',
            'max_amount_xof' => 'integer',
            'is_active' => 'boolean',
            'api_key' => 'encrypted',
            'api_secret' => 'encrypted',
            'is_live' => 'boolean',
        ];
    }

    public function isConfigured(): bool
    {
        return $this->api_key !== null;
    }

    // Pas de colonne `type`/slug dédiée sur ce référentiel (à la différence
    // de `BillProvider`) : seulement deux prestataires réels branchés pour
    // l'instant (OrangeMoneyClient, WaveClient), donc reconnus par leur nom
    // plutôt que d'ajouter une colonne pour deux cas d'usage.
    public function isOrangeMoney(): bool
    {
        return str_contains(strtolower($this->name), 'orange');
    }

    public function isWave(): bool
    {
        return str_contains(strtolower($this->name), 'wave');
    }
}

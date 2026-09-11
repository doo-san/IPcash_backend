<?php

namespace App\Models;

use App\Enums\KycVerificationStatus;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

// Client mobile IPCash — voir `Account` côté Flutter/openapi.yaml. Jamais
// confondu avec `User` (staff admin). Utilise aussi `HasApiTokens` : Sanctum
// gère les tokens de façon polymorphe (`tokenable_type`/`tokenable_id`),
// donc `Account` et `User` peuvent tous deux émettre des tokens sans guard
// dédié — `auth:sanctum` résout le bon modèle depuis le token présenté.
class Account extends Model
{
    use HasApiTokens, HasUuids, LogsAdminActivity;

    // Valeurs par défaut disponibles en mémoire immédiatement après
    // `create()` — les défauts de colonne SQL seuls ne se reflètent pas
    // sur l'instance tant qu'on n'a pas fait `->fresh()`.
    protected $attributes = [
        'kyc_status' => 'notStarted',
        'balance_xof' => 0,
        'failed_pin_attempts' => 0,
        'notifications_enabled' => true,
        'preferred_locale' => 'fr',
        'biometric_enabled' => false,
    ];

    protected $fillable = [
        'phone_number',
        'first_name',
        'last_name',
        'kyc_status',
        'kyc_rejection_reason',
        'email',
        'notifications_enabled',
        'preferred_locale',
        'biometric_enabled',
    ];

    // `pin_hash` volontairement absent de $fillable/toArray implicite —
    // jamais renvoyé par l'API (CLAUDE.md règle 4).
    protected $hidden = ['pin_hash'];

    protected function casts(): array
    {
        return [
            'kyc_status' => KycVerificationStatus::class,
            'balance_xof' => 'integer',
            'failed_pin_attempts' => 'integer',
            'locked_until' => 'datetime',
            'blocked_at' => 'datetime',
            'blocked_until' => 'datetime',
            'notifications_enabled' => 'boolean',
            'biometric_enabled' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function ephemeralCard(): HasOne
    {
        return $this->hasOne(EphemeralCard::class)->whereNull('destroyed_at');
    }

    public function foreignBalances(): HasMany
    {
        return $this->hasMany(ForeignBalance::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function pockets(): HasMany
    {
        return $this->hasMany(Pocket::class);
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function billAccounts(): HasMany
    {
        return $this->hasMany(BillAccount::class);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    // Blocage administratif — distinct de `isLocked()` (auto, le client le
    // lève lui-même en retapant son code plus tard) : coupe tout accès à
    // l'API (connexion et jetons déjà émis, voir `EnsureAccountIsNotBlocked`)
    // jusqu'à ce qu'un membre du staff débloque explicitement le compte
    // (ou que le blocage temporaire expire de lui-même).
    public function isBlockedPermanently(): bool
    {
        return $this->blocked_at !== null;
    }

    public function isBlockedTemporarily(): bool
    {
        return $this->blocked_until !== null && $this->blocked_until->isFuture();
    }

    public function isBlocked(): bool
    {
        return $this->isBlockedPermanently() || $this->isBlockedTemporarily();
    }

    // Dérivé, comme le getter `fullName` côté Flutter (`Account._()`) —
    // `null` tant que l'inscription n'a pas atteint l'étape prénom/nom.
    public function fullName(): ?string
    {
        if ($this->first_name === null && $this->last_name === null) {
            return null;
        }

        return trim("{$this->first_name} {$this->last_name}");
    }
}

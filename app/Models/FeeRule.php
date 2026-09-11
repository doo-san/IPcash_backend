<?php

namespace App\Models;

use App\Enums\FeeScope;
use App\Enums\FeeType;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class FeeRule extends Model
{
    use LogsAdminActivity;

    protected $attributes = ['is_active' => true];

    protected $fillable = ['scope', 'type', 'value', 'min_fee_xof', 'max_fee_xof', 'is_active'];

    protected function casts(): array
    {
        return [
            'scope' => FeeScope::class,
            'type' => FeeType::class,
            'value' => 'integer',
            'min_fee_xof' => 'integer',
            'max_fee_xof' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Calcule les frais pour un montant donné — reproduit
     * `_feeRateBasisPoints` de transfer_fake_data_source.dart, sans
     * encore y être branché (voir CLAUDE.md règle 9).
     */
    public function feeFor(int $amountXof): int
    {
        $fee = $this->type === FeeType::Percent
            ? intdiv($amountXof * $this->value, 10000)
            : $this->value;

        if ($this->min_fee_xof !== null) {
            $fee = max($fee, $this->min_fee_xof);
        }
        if ($this->max_fee_xof !== null) {
            $fee = min($fee, $this->max_fee_xof);
        }

        return $fee;
    }

    /**
     * Recherche la règle active du scope donné et calcule les frais —
     * 0 si aucune règle active n'existe pour ce scope (comportement actuel
     * par défaut, inchangé tant qu'un opérateur n'en crée pas une dans
     * l'admin). Partagé par tous les contrôleurs qui déplacent de
     * l'argent (transferts, cash-in/out, factures, recharge de carte) —
     * réplique `TransferController::computeFee`, qui était jusqu'ici le
     * seul scope réellement branché.
     */
    public static function computeFor(FeeScope $scope, int $amountXof): int
    {
        $rule = static::where('scope', $scope->value)->where('is_active', true)->first();

        return $rule?->feeFor($amountXof) ?? 0;
    }
}

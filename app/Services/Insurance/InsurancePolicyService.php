<?php

namespace App\Services\Insurance;

use App\Enums\InsurancePolicyStatus;
use App\Enums\InsuranceType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\InsurancePolicy;
use App\Models\User;
use App\Support\AppConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

// Décision admin sur un contrat `pending` (InsurancePolicyResource). Les
// deux issues verrouillent le contrat (`FOR UPDATE`) : deux clics simultanés
// ne peuvent ni émettre deux attestations, ni rembourser deux fois.
class InsurancePolicyService
{
    // Mentions sans lesquelles aucune attestation n'est émise (voir le
    // groupe `insurance` de config/app_config.php).
    private const REQUIRED_SETTINGS = [
        'insurance_insurer_name',
        'insurance_intermediary_name',
        'insurance_agreement_number',
        'insurance_guarantees',
    ];

    /** @return list<string> Libellés des mentions encore vides. */
    public static function missingSettings(): array
    {
        $missing = [];
        foreach (self::REQUIRED_SETTINGS as $key) {
            if (trim(AppConfig::string($key)) === '') {
                $missing[] = AppConfig::fields()[$key]['label'];
            }
        }

        return $missing;
    }

    public function approve(InsurancePolicy $policy, User $reviewer): InsurancePolicy
    {
        if (self::missingSettings() !== []) {
            throw new InsurancePolicyDecisionException('Les mentions de l\'attestation ne sont pas toutes renseignées.');
        }

        return DB::transaction(function () use ($policy, $reviewer) {
            $policy = $this->lockPending($policy);

            $policy->policy_number = $this->newPolicyNumber($policy->insurance_type);
            // « Du 23/10 au 22/04 inclus » pour 6 mois : la couverture
            // s'arrête la veille du même quantième, en fin de journée.
            $policy->starts_at = now();
            $policy->ends_at = now()->addMonthsNoOverflow($policy->duration_months)->subDay()->endOfDay();
            $policy->status = InsurancePolicyStatus::Active;
            $policy->reviewed_by = $reviewer->id;
            $policy->reviewed_at = now();

            $path = "{$policy->account_id}/{$policy->id}.pdf";
            if (! Storage::disk('insurance')->put($path, $this->renderAttestation($policy))) {
                throw new RuntimeException('Écriture de l\'attestation impossible.');
            }
            $policy->attestation_path = $path;
            $policy->save();

            return $policy;
        });
    }

    public function reject(InsurancePolicy $policy, User $reviewer, string $reason): InsurancePolicy
    {
        return DB::transaction(function () use ($policy, $reviewer, $reason) {
            $policy = $this->lockPending($policy);
            /** @var Account $account */
            $account = Account::whereKey($policy->account_id)->lockForUpdate()->firstOrFail();
            $refundXof = $policy->price_xof + $policy->fee_xof;

            $account->increment('balance_xof', $refundXof);
            $refund = $account->transactions()->create([
                'type' => TransactionType::InsuranceRefund,
                'status' => TransactionStatus::Completed,
                'amount_xof' => $refundXof,
                'note' => $policy->plate_number,
                'reference' => 'INSUR-REF-'.strtoupper(Str::random(10)),
                'main_balance_after_xof' => $account->balance_xof,
            ]);

            $policy->status = InsurancePolicyStatus::Rejected;
            $policy->rejection_reason = $reason;
            $policy->refund_transaction_id = $refund->id;
            $policy->reviewed_by = $reviewer->id;
            $policy->reviewed_at = now();
            $policy->save();

            return $policy;
        });
    }

    public function renderAttestation(InsurancePolicy $policy): string
    {
        return Pdf::loadView('pdf.insurance-attestation', [
            'policy' => $policy,
            'account' => $policy->account,
            'insurerName' => AppConfig::string('insurance_insurer_name'),
            'intermediaryName' => AppConfig::string('insurance_intermediary_name'),
            'agreementNumber' => AppConfig::string('insurance_agreement_number'),
            'guarantees' => AppConfig::string('insurance_guarantees'),
        ])->setPaper('a4')->output();
    }

    private function lockPending(InsurancePolicy $policy): InsurancePolicy
    {
        /** @var InsurancePolicy $locked */
        $locked = InsurancePolicy::whereKey($policy->getKey())->lockForUpdate()->firstOrFail();
        if ($locked->status !== InsurancePolicyStatus::Pending) {
            throw new InsurancePolicyDecisionException('Ce contrat a déjà été traité.');
        }

        return $locked;
    }

    private function newPolicyNumber(InsuranceType $type): string
    {
        $prefix = $type === InsuranceType::Auto ? 'A' : 'M';

        do {
            $number = 'IPC-'.$prefix.'-'.now()->format('Y').'-'.strtoupper(Str::random(8));
        } while (InsurancePolicy::where('policy_number', $number)->exists());

        return $number;
    }
}

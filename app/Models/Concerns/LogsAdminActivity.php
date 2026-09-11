<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Journal d'audit obligatoire : « toute modification doit avoir une
 * traçabilité » (exigence produit). Journalise automatiquement toute
 * création/modification/suppression effectuée par un membre du staff —
 * qui a fait quoi, sur quel enregistrement, quand, avec l'état avant/après.
 *
 * Scopé au staff via `auth()->user() instanceof User` plutôt que via
 * `Filament::isServing()` : ce flag n'est posé que par le middleware HTTP
 * persistant de Livewire, que `Livewire::test()` (toute la suite de tests
 * de ce projet) contourne — s'y fier aurait rendu la journalisation muette
 * dans les tests tout en fonctionnant en navigateur réel, un piège classique.
 * Le guard `web` (staff, `User`) n'est jamais utilisé par l'API mobile
 * (guard `sanctum`, modèle `Account`) : ce test suffit donc à isoler « une
 * action du staff » de « une opération normale d'un client », sans dépendre
 * d'un détail d'implémentation du panneau. Couvre aussi bien les actions
 * personnalisées (déverrouiller, résoudre/inverser une transaction,
 * approuver/rejeter un KYC…) que les formulaires standards d'édition/
 * création/suppression des ressources Filament : tout passe par
 * `save()`/`delete()` d'Eloquent, donc un seul point d'accroche suffit.
 *
 * Les colonnes sensibles (`$hidden` du modèle : pin_hash, api_key, api_secret…)
 * sont retirées avant journalisation, qu'elles apparaissent dans l'état
 * complet ou dans le diff des changements.
 */
trait LogsAdminActivity
{
    protected static function bootLogsAdminActivity(): void
    {
        static::created(fn (Model $model) => static::recordAdminActivity($model, 'created'));
        static::updated(fn (Model $model) => static::recordAdminActivity($model, 'updated'));
        static::deleted(fn (Model $model) => static::recordAdminActivity($model, 'deleted'));
    }

    protected static function recordAdminActivity(Model $model, string $event): void
    {
        if (! auth()->user() instanceof User) {
            return;
        }

        $hidden = $model->getHidden();

        if ($event === 'updated') {
            $changes = collect($model->getChanges())
                ->except(array_merge($hidden, ['updated_at']))
                ->all();

            if ($changes === []) {
                return; // rien de significatif à tracer (ex. simple touch())
            }

            $properties = [
                'attributes' => $changes,
                'old' => collect($model->getOriginal())
                    ->only(array_keys($changes))
                    ->all(),
            ];
        } elseif ($event === 'created') {
            $properties = [
                'attributes' => collect($model->getAttributes())->except($hidden)->all(),
            ];
        } else {
            $properties = [
                'attributes' => collect($model->getOriginal())->except($hidden)->all(),
            ];
        }

        activity('admin')
            ->causedBy(auth()->user())
            ->performedOn($model)
            ->withProperties($properties)
            ->event($event)
            ->log($event);
    }
}

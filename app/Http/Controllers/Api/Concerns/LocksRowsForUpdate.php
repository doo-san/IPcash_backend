<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Relit une ligne avec un verrou pessimiste (`SELECT ... FOR UPDATE`) juste
 * avant de contrôler puis débiter un solde — sans ce verrou, deux requêtes
 * concurrentes (deux transferts différents, par ex.) peuvent chacune lire le
 * même solde en mémoire, chacune le juger suffisant, puis chacune débiter :
 * le solde final peut devenir négatif (double dépense) même si chaque
 * décrément pris isolément est une opération SQL atomique. Toujours appelé
 * à l'intérieur d'un `DB::transaction()`, jamais en dehors — le verrou ne
 * vaut que pour la durée de la transaction englobante.
 */
trait LocksRowsForUpdate
{
    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    protected function lockForUpdate(Model $model): Model
    {
        return $model->newQuery()->whereKey($model->getKey())->lockForUpdate()->firstOrFail();
    }
}

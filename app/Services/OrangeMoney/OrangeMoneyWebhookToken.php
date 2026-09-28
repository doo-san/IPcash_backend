<?php

namespace App\Services\OrangeMoney;

// Le webhook OM Pay est appelé par Orange sans jeton ni signature documentés :
// n'importe qui connaissant une référence pouvait sinon poster « SUCCESS » et
// faire créditer un solde. On lie donc l'URL de rappel remise à Orange à la
// référence du paiement par un HMAC de la clé applicative (inconnu de tout
// tiers) : seul l'appel rendu à cette URL exacte est accepté, et un jeton ne
// vaut que pour sa propre référence.
class OrangeMoneyWebhookToken
{
    public static function for(string $reference): string
    {
        return hash_hmac('sha256', 'orange-money-webhook:'.$reference, (string) config('app.key'));
    }

    public static function isValid(string $reference, string $token): bool
    {
        return hash_equals(self::for($reference), $token);
    }
}

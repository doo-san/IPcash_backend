<?php

namespace App\Services\Wave;

// Schéma de signature Wave (voir docs.wave.com) — identique dans les deux
// sens : nos requêtes sortantes (`Wave-Signature` exigé quand la clé API a
// la signature de requête activée, confirmé en pratique : un appel sans cet
// en-tête renvoie `401 missing-signature`) et les webhooks entrants que
// Wave nous envoie. `payload = timestamp . corps brut`, HMAC-SHA256.
class WaveSignature
{
    public static function header(string $secret, string $rawBody): string
    {
        $timestamp = (string) time();

        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.$rawBody, $secret);
    }

    // Rejette aussi un timestamp hors fenêtre (5 min dans le passé, 30 s
    // dans le futur) — même tolérance que documentée côté Wave, pour éviter
    // qu'un webhook rejoué plus tard ne soit accepté.
    public static function verify(string $header, string $secret, string $rawBody): bool
    {
        $parts = [];
        foreach (explode(',', $header) as $segment) {
            [$key, $value] = array_pad(explode('=', $segment, 2), 2, null);
            if ($key !== null) {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['t'] ?? null;
        $signature = $parts['v1'] ?? null;
        if ($timestamp === null || $signature === null || ! ctype_digit($timestamp)) {
            return false;
        }

        $age = time() - (int) $timestamp;
        if ($age > 300 || $age < -30) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.$rawBody, $secret);

        return hash_equals($expected, $signature);
    }
}

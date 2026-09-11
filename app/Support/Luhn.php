<?php

namespace App\Support;

class Luhn
{
    /**
     * Chiffre de contrôle Luhn valide pour une séquence de chiffres (le
     * chiffre retourné serait ajouté à droite pour former un numéro
     * valide) — voir `EphemeralCard`, qui l'utilise à l'envers pour
     * générer volontairement un numéro invalide.
     *
     * @param  array<int, int>  $digits
     */
    public static function checkDigit(array $digits): int
    {
        $sum = 0;
        $double = true;
        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $d = $digits[$i];
            if ($double) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $sum += $d;
            $double = ! $double;
        }

        return (10 - ($sum % 10)) % 10;
    }
}

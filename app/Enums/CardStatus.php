<?php

namespace App\Enums;

enum CardStatus: string
{
    case Active = 'active';
    // Gel réversible par le client lui-même depuis l'app.
    case Frozen = 'frozen';
    // Blocage administratif : le client ne peut pas le lever, seul le staff
    // le peut (voir CardController::unfreeze, CardResource / EditCard).
    case Blocked = 'blocked';
}

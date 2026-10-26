<?php

namespace App\Exceptions;

use RuntimeException;

// Levée à l'intérieur d'un `DB::transaction()` dès que le verrou pessimiste
// (voir `LocksRowsForUpdate`) révèle un solde insuffisant une fois la ligne
// relue sous verrou — l'exception annule automatiquement la transaction
// (aucune écriture partielle), le contrôleur appelant la convertit en
// réponse 402 INSUFFICIENT_FUNDS.
class InsufficientFundsException extends RuntimeException {}

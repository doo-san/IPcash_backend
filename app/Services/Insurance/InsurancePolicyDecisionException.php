<?php

namespace App\Services\Insurance;

use RuntimeException;

// Décision impossible sur un contrat (déjà traité, mentions manquantes) —
// le message est destiné à l'admin, jamais au client.
class InsurancePolicyDecisionException extends RuntimeException {}

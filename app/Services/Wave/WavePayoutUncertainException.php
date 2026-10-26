<?php

namespace App\Services\Wave;

// Issue inconnue d'un payout (délai dépassé, erreur 5xx, réponse illisible) :
// Wave a peut-être payé. Jamais traité comme un échec (règle 3) — la
// transaction reste `pending` pour rapprochement.
class WavePayoutUncertainException extends WaveException {}

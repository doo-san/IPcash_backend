<?php

namespace App\Enums;

// Miroir exact de `KycVerificationStatus` dans api/openapi.yaml — ne pas
// faire dériver l'ordre/les valeurs sans mettre à jour le contrat.
enum KycVerificationStatus: string
{
    case NotStarted = 'notStarted';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
    case Review = 'review';
}

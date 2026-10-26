<?php

namespace App\Enums;

// Miroir de `InsurancePolicyStatus` dans api/openapi.yaml. `pending` : payé,
// en attente de validation par un `super_admin` ; `active` : attestation
// émise ; `rejected` : refusé et remboursé.
enum InsurancePolicyStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
}

<?php

return [
    // Durée de conservation des données légales d'un compte clôturé
    // (identité, pièces KYC, transactions) : loi uniforme UEMOA LBC/FT.
    // À confirmer par la conformité.
    'closed_retention_years' => (int) env('CLOSED_ACCOUNT_RETENTION_YEARS', 10),
];

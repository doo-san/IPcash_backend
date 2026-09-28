<?php

// Réglages de l'application mobile modifiables depuis l'admin ("Réglages de
// l'application", App\Filament\Pages\AppConfigPage), stockés dans
// `app_settings` et lus via App\Support\AppConfig. Un champ absent de la
// base retombe sur son `default`. Chaque réglage ajouté ici doit être
// réellement appliqué par l'API (voir les tests) : pas de bouton sans effet.
//
// types : text, integer, toggle, textarea.
return [
    'general' => [
        'label' => 'Général',
        'fields' => [
            'support_phone_number' => [
                'label' => "Numéro d'appel du support",
                'type' => 'text',
                'default' => '+221338000000',
            ],
        ],
    ],

    'limits' => [
        'label' => 'Limites',
        'fields' => [
            'transfer_min_amount_xof' => [
                'label' => "Montant minimum d'un transfert (F)",
                'type' => 'integer',
                'default' => 5,
                'min' => 1,
            ],
        ],
    ],

    'maintenance' => [
        'label' => 'Maintenance',
        'fields' => [
            'maintenance_enabled' => [
                'label' => "Mettre l'application en maintenance",
                'type' => 'toggle',
                'default' => false,
            ],
            'maintenance_message' => [
                'label' => 'Message affiché aux clients',
                'type' => 'textarea',
                'default' => '',
            ],
        ],
    ],

    'versions' => [
        'label' => "Version minimale de l'application",
        'fields' => [
            'min_app_version_ios' => [
                'label' => 'iOS (ex. 1.2.0)',
                'type' => 'text',
                'default' => '',
            ],
            'min_app_version_android' => [
                'label' => 'Android (ex. 1.2.0)',
                'type' => 'text',
                'default' => '',
            ],
        ],
    ],

    // Chaque service désactivé renvoie 403 FEATURE_DISABLED sur toutes ses
    // routes (voir EnsureFeatureEnabled et routes/api.php).
    'features' => [
        'label' => 'Services',
        'fields' => [
            'feature_transfer_enabled' => ['label' => 'Transfert d\'argent', 'type' => 'toggle', 'default' => true],
            'feature_cashio_enabled' => ['label' => 'Dépôt, retrait et XChange', 'type' => 'toggle', 'default' => true],
            'feature_cashchange_enabled' => ['label' => 'CashChange (sous-comptes devise)', 'type' => 'toggle', 'default' => true],
            'feature_payment_enabled' => ['label' => 'Paiement (factures, QR, marchands)', 'type' => 'toggle', 'default' => true],
            'feature_credit_enabled' => ['label' => 'Crédit téléphonique', 'type' => 'toggle', 'default' => true],
            'feature_esim_enabled' => ['label' => 'eSIM', 'type' => 'toggle', 'default' => true],
            'feature_insurance_enabled' => ['label' => 'Assurance', 'type' => 'toggle', 'default' => true],
            'feature_card_enabled' => ['label' => 'Carte prépayée', 'type' => 'toggle', 'default' => true],
            'feature_pockets_enabled' => ['label' => "Poches d'épargne", 'type' => 'toggle', 'default' => true],
        ],
    ],
];

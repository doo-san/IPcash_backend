<?php

// Réglages globaux du site public modifiables depuis l'admin
// (App\Filament\Pages\SiteSettingsPage) : liens réseaux sociaux, liens de
// téléchargement (App Store/Google Play) et images. `default` pour un lien
// est la valeur utilisée tant qu'aucun SiteSetting n'existe pour cette clé
// (souvent vide — le lien reste alors un ancre locale `#telecharger` posée
// directement dans la vue) ; `default` pour une image est le chemin
// relatif sous `public/` déjà utilisé dans les vues (voir
// app/Support/helpers.php, site_setting_image_url()).
//
// Ajouter un champ : une entrée ici + remplacer la valeur en dur par
// site_setting('key') ou site_setting_image_url('key') dans la vue.
return [
    'links' => [
        'label' => 'Réseaux sociaux & téléchargement',
        'fields' => [
            'social_linkedin' => ['label' => 'LinkedIn', 'type' => 'url', 'default' => ''],
            'social_instagram' => ['label' => 'Instagram', 'type' => 'url', 'default' => ''],
            'social_x' => ['label' => 'X (Twitter)', 'type' => 'url', 'default' => ''],
            'social_facebook' => ['label' => 'Facebook', 'type' => 'url', 'default' => ''],
            'store_app_store' => ['label' => 'Lien App Store', 'type' => 'url', 'default' => ''],
            'store_google_play' => ['label' => 'Lien Google Play', 'type' => 'url', 'default' => ''],
        ],
    ],

    'images' => [
        'label' => 'Images',
        'fields' => [
            'logo' => [
                'label' => 'Logo (header et footer)',
                'type' => 'image',
                'default' => 'images/ipcash-icon.svg',
            ],
            'hero_screenshot' => [
                'label' => "Capture d'écran du hero (accueil)",
                'type' => 'image',
                'default' => 'images/screenshots/dashboard.png',
            ],
            'security_background' => [
                'label' => 'Fond de la section sécurité (accueil)',
                'type' => 'image',
                'default' => 'images/backgrounds/security-section.jpeg',
            ],
            'partner_orange_money' => [
                'label' => 'Logo Orange Money',
                'type' => 'image',
                'default' => 'images/partners/orange_money.svg',
            ],
            'partner_wave' => [
                'label' => 'Logo Wave',
                'type' => 'image',
                'default' => 'images/partners/wave.svg',
            ],
            'partner_mixx_by_yas' => [
                'label' => 'Logo Mixx by Yas',
                'type' => 'image',
                'default' => 'images/partners/mixx_by_yas.svg',
            ],
        ],
    ],
];

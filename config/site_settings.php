<?php

// Réglages globaux du site public modifiables depuis l'admin, organisés
// comme les sous-pages du groupe de navigation "Site public" (voir
// App\Filament\Pages\Site{Header,Footer,Seo,Images}Page) : contenu de
// l'en-tête, contenu du pied de page, référencement (SEO) et le reste des
// images. `default` pour un texte/lien est la valeur utilisée tant
// qu'aucun réglage n'a été enregistré (souvent vide — le lien reste alors
// une ancre locale posée directement dans la vue) ; `default` pour une
// image est le chemin relatif sous `public/` déjà utilisé dans les vues.
// Voir app/Support/helpers.php (site_setting()/site_setting_image_url()).
//
// Ajouter un champ : une entrée ici + remplacer la valeur en dur par
// site_setting('key') ou site_setting_image_url('key') dans la vue.
return [
    'header' => [
        'label' => "Contenu de l'en-tête",
        'fields' => [
            'logo' => [
                'label' => 'Logo (en-tête et pied de page)',
                'type' => 'image',
                'default' => 'images/ipcash-icon.svg',
            ],
            'store_app_store' => ['label' => 'Lien App Store', 'type' => 'url', 'default' => ''],
            'store_google_play' => ['label' => 'Lien Google Play', 'type' => 'url', 'default' => ''],
        ],
    ],

    'footer' => [
        'label' => 'Contenu du pied de page',
        'fields' => [
            'footer_tagline' => [
                'label' => 'Texte sous le logo',
                'type' => 'textarea',
                'default' => "La super-app financière pensée pour le Sénégal et l'UEMOA — transférez, épargnez et payez depuis une seule application.",
            ],
            'social_linkedin' => ['label' => 'LinkedIn', 'type' => 'url', 'default' => ''],
            'social_instagram' => ['label' => 'Instagram', 'type' => 'url', 'default' => ''],
            'social_x' => ['label' => 'X (Twitter)', 'type' => 'url', 'default' => ''],
            'social_facebook' => ['label' => 'Facebook', 'type' => 'url', 'default' => ''],
            'legal_terms_url' => ['label' => "Lien \"Conditions d'utilisation\"", 'type' => 'url', 'default' => ''],
            'legal_privacy_url' => ['label' => 'Lien "Confidentialité"', 'type' => 'url', 'default' => ''],
        ],
    ],

    'seo' => [
        'label' => 'Référencement (SEO)',
        'fields' => [
            'seo_home_title' => ['label' => 'Accueil — titre', 'type' => 'text', 'default' => 'Accueil'],
            'seo_home_description' => [
                'label' => 'Accueil — description',
                'type' => 'textarea',
                'default' => "Transférez, épargnez, payez et changez de devise depuis une seule application. IPCash, la néobanque pensée pour le Sénégal et l'UEMOA.",
            ],
            'seo_features_title' => ['label' => 'Fonctionnalités — titre', 'type' => 'text', 'default' => 'Fonctionnalités'],
            'seo_features_description' => [
                'label' => 'Fonctionnalités — description',
                'type' => 'textarea',
                'default' => 'Toutes les fonctionnalités IPCash en détail : transfert, mobile money, IPchange, carte virtuelle, épargne, factures, crédit, eSIM et assurance.',
            ],
            'seo_security_title' => ['label' => 'Sécurité — titre', 'type' => 'text', 'default' => 'Sécurité'],
            'seo_security_description' => [
                'label' => 'Sécurité — description',
                'type' => 'textarea',
                'default' => "Comment IPCash protège votre argent et vos données : vérification d'identité, code PIN et biométrie, chiffrement, contrôle des sessions et blocage immédiat.",
            ],
            'seo_about_title' => ['label' => 'À propos — titre', 'type' => 'text', 'default' => 'À propos'],
            'seo_about_description' => [
                'label' => 'À propos — description',
                'type' => 'textarea',
                'default' => "La mission d'IPCash : rendre les services financiers du quotidien accessibles depuis un seul téléphone, partout en UEMOA.",
            ],
            'seo_contact_title' => ['label' => 'Contact — titre', 'type' => 'text', 'default' => 'Contact'],
            'seo_contact_description' => [
                'label' => 'Contact — description',
                'type' => 'textarea',
                'default' => "Contactez l'équipe IPCash pour toute question, partenariat ou remarque.",
            ],
        ],
    ],

    'images' => [
        'label' => 'Autres images',
        'fields' => [
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

<?php

// Textes clés du site public modifiables depuis l'admin (App\Filament\Pages
// \SiteContentPage) sans toucher au code — la mise en page/design reste
// fixée dans les vues Blade (resources/views/site/*.blade.php), seul le
// texte change. `default` est la valeur affichée tant qu'aucune ligne
// SiteContent n'existe pour ce couple page/key (voir site_content() dans
// app/Support/helpers.php) : c'est le texte déjà en dur dans les vues au
// moment où cet écran d'admin a été introduit, donc rien ne change
// visuellement tant que personne n'édite quoi que ce soit.
//
// Ajouter un champ éditable : une entrée ici (avec le texte actuel de la
// vue comme `default`) + remplacer le texte en dur par
// site_content('page', 'key') dans le Blade correspondant. Pas de
// migration nécessaire, la ligne n'est créée en base qu'à la sauvegarde.
return [
    'home' => [
        'label' => 'Accueil',
        'fields' => [
            'hero_subtitle' => [
                'label' => 'Sous-titre du hero',
                'type' => 'textarea',
                'default' => 'Transférez, épargnez, payez vos factures et changez de devise — tout depuis une seule application, sans passer par une agence.',
            ],
            'features_heading' => [
                'label' => 'Titre de la section fonctionnalités',
                'type' => 'text',
                'default' => 'Votre quotidien en quelques clics',
            ],
            'steps_heading' => [
                'label' => 'Titre de la section "3 étapes"',
                'type' => 'text',
                'default' => 'Trois étapes, et c\'est fait',
            ],
            'steps_subheading' => [
                'label' => 'Sous-titre de la section "3 étapes"',
                'type' => 'text',
                'default' => 'Pas de dossier, pas de rendez-vous en agence.',
            ],
            'security_heading' => [
                'label' => 'Titre de la section sécurité',
                'type' => 'text',
                'default' => 'Votre sécurité, notre priorité',
            ],
            'security_subheading' => [
                'label' => 'Sous-titre de la section sécurité',
                'type' => 'text',
                'default' => 'Votre argent et vos données sont protégés à chaque étape.',
            ],
            'faq_heading' => [
                'label' => 'Titre de la FAQ',
                'type' => 'text',
                'default' => 'Tout ce qu\'il faut savoir',
            ],
            'cta_heading' => [
                'label' => 'Titre du bandeau final',
                'type' => 'text',
                'default' => 'Prêt à simplifier votre argent ?',
            ],
            'cta_subheading' => [
                'label' => 'Sous-titre du bandeau final',
                'type' => 'text',
                'default' => 'IPCash arrive bientôt sur l\'App Store et Google Play.',
            ],
        ],
    ],

    'features' => [
        'label' => 'Fonctionnalités',
        'fields' => [
            'hero_title' => [
                'label' => 'Titre du hero',
                'type' => 'text',
                'default' => 'Une app, huit façons de simplifier votre argent.',
            ],
            'hero_subtitle' => [
                'label' => 'Sous-titre du hero',
                'type' => 'textarea',
                'default' => "Du transfert instantané à l'assurance auto, chaque service IPCash est pensé pour remplacer une file d'attente par quelques secondes sur votre téléphone.",
            ],
            'secondary_heading' => [
                'label' => 'Titre de la section "et pour le reste"',
                'type' => 'text',
                'default' => 'Et pour le reste, on a pensé à tout',
            ],
            'secondary_subheading' => [
                'label' => 'Sous-titre de la section "et pour le reste"',
                'type' => 'text',
                'default' => 'Cinq autres services, toujours dans la même app.',
            ],
            'cta_heading' => [
                'label' => 'Titre du bandeau final',
                'type' => 'text',
                'default' => 'Découvrez tout ça par vous-même',
            ],
            'cta_subheading' => [
                'label' => 'Sous-titre du bandeau final',
                'type' => 'text',
                'default' => 'IPCash arrive bientôt sur l\'App Store et Google Play.',
            ],
        ],
    ],

    'security' => [
        'label' => 'Sécurité',
        'fields' => [
            'hero_title' => [
                'label' => 'Titre du hero',
                'type' => 'text',
                'default' => 'Conçu pour que vous gardiez toujours le contrôle.',
            ],
            'hero_subtitle' => [
                'label' => 'Sous-titre du hero',
                'type' => 'textarea',
                'default' => "Une néobanque se juge à la confiance qu'elle inspire. Voici, concrètement, comment votre argent et vos données sont protégés à chaque étape.",
            ],
            'principles_heading' => [
                'label' => 'Titre de la section "principes techniques"',
                'type' => 'text',
                'default' => 'Les principes qui guident chaque décision technique',
            ],
            'cta_heading' => [
                'label' => 'Titre du bandeau final',
                'type' => 'text',
                'default' => 'Des questions sur la sécurité de vos données ?',
            ],
            'cta_subheading' => [
                'label' => 'Sous-titre du bandeau final',
                'type' => 'text',
                'default' => 'Notre équipe vous répond directement.',
            ],
        ],
    ],

    'about' => [
        'label' => 'À propos',
        'fields' => [
            'hero_title' => [
                'label' => 'Titre du hero',
                'type' => 'text',
                'default' => 'Simplifier l\'argent, pour tout le monde.',
            ],
            'hero_subtitle' => [
                'label' => 'Sous-titre du hero',
                'type' => 'textarea',
                'default' => "IPCash est né d'un constat simple : gérer son argent au quotidien demande encore trop souvent de jongler entre plusieurs opérateurs, agences et applications.",
            ],
            'mission_lead' => [
                'label' => 'Citation de mission (grand texte)',
                'type' => 'textarea',
                'default' => "Remplacer plusieurs applications, agences et files d'attente par une seule — pour la zone UEMOA, en commençant par le Sénégal.",
            ],
            'mission_sub' => [
                'label' => 'Texte de mission (paragraphe)',
                'type' => 'textarea',
                'default' => 'Nous croyons qu\'un service financier doit être aussi simple à utiliser qu\'à comprendre : pas de jargon, pas de frais cachés, et un statut clair sur chaque opération — en cours, confirmée ou échouée, jamais autre chose.',
            ],
            'values_heading' => [
                'label' => 'Titre de la section "3 principes"',
                'type' => 'text',
                'default' => 'Trois principes qui guident chaque décision',
            ],
            'commitments_heading' => [
                'label' => 'Titre de la section "engagements"',
                'type' => 'text',
                'default' => 'Ce que nous vous devons',
            ],
            'commitments_subheading' => [
                'label' => 'Sous-titre de la section "engagements"',
                'type' => 'text',
                'default' => 'Un engagement, pas une promesse marketing.',
            ],
            'cta_heading' => [
                'label' => 'Titre du bandeau final',
                'type' => 'text',
                'default' => 'Envie d\'en discuter ?',
            ],
            'cta_subheading' => [
                'label' => 'Sous-titre du bandeau final',
                'type' => 'text',
                'default' => 'Une question, un partenariat, une remarque — écrivez-nous.',
            ],
        ],
    ],

    'contact' => [
        'label' => 'Contact',
        'fields' => [
            'hero_title' => [
                'label' => 'Titre du hero',
                'type' => 'text',
                'default' => 'Contactez-nous.',
            ],
            'panel_heading' => [
                'label' => 'Titre du panneau "Autrement"',
                'type' => 'text',
                'default' => 'Autrement',
            ],
            'panel_subheading' => [
                'label' => 'Texte du panneau "Autrement"',
                'type' => 'textarea',
                'default' => 'Vous préférez ne pas passer par le formulaire ? Voici comment nous joindre.',
            ],
        ],
    ],
];

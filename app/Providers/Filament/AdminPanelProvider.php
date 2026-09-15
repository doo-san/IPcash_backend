<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Asset CSS simple (pas un thème Filament complet — celui-ci exige
        // Tailwind v3, or ce projet utilise déjà Tailwind v4 pour
        // resources/css/app.css) : distingue visuellement la barre latérale
        // de la zone de contenu. Voir le commentaire dans le fichier CSS.
        // `php artisan filament:assets` copie ce fichier vers /public à
        // chaque déploiement/changement.
        FilamentAsset::register([
            Css::make('admin-sidebar', resource_path('css/filament/admin-sidebar.css')),
            Css::make('admin-login', resource_path('css/filament/admin-login.css')),
        ]);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->brandName('IPCash Admin')
            // Logo IPCash — même paire de fichiers que `IpWordmark` côté
            // Flutter (`Color` sur fond clair, `light` tout blanc sur fond
            // sombre). Remplace le nom textuel dans la barre latérale et
            // sur l'écran de connexion (les deux utilisent `brandLogo`).
            ->brandLogo(asset('images/ipcash-logo.svg'))
            ->darkModeBrandLogo(asset('images/ipcash-logo-dark.svg'))
            ->brandLogoHeight('5rem')
            ->colors([
                // Vert IPCash (`IpColors.green` côté Flutter) plutôt que la
                // couleur par défaut de Filament, pour une identité visuelle
                // cohérente entre l'app mobile et l'admin.
                'primary' => Color::hex('#00A05B'),
                // Ardoise plutôt que le gris par défaut de Filament — un
                // neutre plus froid/soutenu, d'aspect plus « produit »
                // (même famille que les interfaces type Linear/Notion)
                // pour le texte secondaire, les bordures et la barre
                // latérale.
                'gray' => Color::Slate,
            ])
            // Ordre et icônes explicites plutôt que l'ordre alphabétique
            // par défaut (qui plaçait « Audit » avant « Clients ») — du
            // plus consulté au moins consulté. Les groupes de config/
            // contenu, feuilletés bien moins souvent, démarrent repliés
            // pour une barre latérale plus courte et plus lisible au
            // quotidien.
            ->navigationGroups([
                NavigationGroup::make('Clients')
                    ->icon('heroicon-o-user-group'),
                NavigationGroup::make('Argent')
                    ->icon('heroicon-o-banknotes'),
                NavigationGroup::make('Contenu')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->collapsed(),
                NavigationGroup::make('Configuration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                NavigationGroup::make('Audit')
                    ->icon('heroicon-o-shield-check')
                    ->collapsed(),
            ])
            // Barre latérale réductible en icônes seules sur ordinateur —
            // plus d'espace pour le contenu une fois l'admin pris en main,
            // comme la plupart des back-offices modernes.
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            // Auto-découverte suffit désormais : les widgets métier
            // (`app/Filament/Widgets/`) remplacent les deux widgets
            // d'exemple Filament (compte connecté, version du framework —
            // sans intérêt pour un tableau de bord admin réel).
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

<?php

use App\Models\LegalPage;
use App\Models\SiteContent;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

if (! function_exists('site_content')) {
    // Texte du site public éventuellement surchargé depuis l'admin (voir
    // App\Filament\Pages\SiteContentPage et config/site_content.php) —
    // retombe sur le défaut du config si aucune ligne SiteContent
    // n'existe, ou si sa valeur est vide (un admin qui vide un champ
    // revient au texte par défaut plutôt que d'afficher une section
    // blanche par erreur).
    //
    // Une requête par appel, volontairement pas de cache process-local :
    // une page du site public en fait une dizaine, sur une table minuscule
    // et indexée (page,key) — le coût est négligeable, et ça évite toute
    // fenêtre où une modification tout juste enregistrée resterait invisible
    // le temps que jusqu'à la fin d'un cache non invalidé.
    function site_content(string $page, string $key): string
    {
        $default = (string) config("site_content.{$page}.fields.{$key}.default", '');

        $value = SiteContent::query()
            ->where('page', $page)
            ->where('key', $key)
            ->value('value');

        return filled($value) ? $value : $default;
    }
}

if (! function_exists('site_setting_field_default')) {
    /**
     * @return array{label: string, type: string, default: string}|null
     */
    function site_setting_field_default(string $key): ?array
    {
        foreach (config('site_settings', []) as $group) {
            if (isset($group['fields'][$key])) {
                return $group['fields'][$key];
            }
        }

        return null;
    }
}

if (! function_exists('site_setting')) {
    // Lien (réseau social, App Store/Google Play…) éventuellement défini
    // depuis l'admin (voir App\Filament\Pages\SiteSettingsPage et
    // config/site_settings.php) — chaîne vide par défaut (pas encore
    // renseigné), à distinguer d'un `null` via `?:` côté vue pour garder
    // un repli local (ex. `site_setting('store_app_store') ?: '#telecharger'`).
    function site_setting(string $key): string
    {
        $default = site_setting_field_default($key)['default'] ?? '';
        $value = SiteSetting::query()->where('key', $key)->value('value');

        return filled($value) ? $value : $default;
    }
}

if (! function_exists('site_setting_image_url')) {
    // URL affichable d'une image du site — celle envoyée depuis l'admin
    // (stockée sur le disque `public`, voir SiteSettingsPage) si présente,
    // sinon l'asset livré avec le code (voir config/site_settings.php).
    function site_setting_image_url(string $key): string
    {
        $default = site_setting_field_default($key)['default'] ?? '';
        $value = SiteSetting::query()->where('key', $key)->value('value');

        return filled($value) ? Storage::disk('public')->url($value) : asset($default);
    }
}

if (! function_exists('legal_pages')) {
    // Pages légales (Conditions d'utilisation, Confidentialité...) éditées
    // depuis l'admin (App\Filament\Resources\LegalPageResource) — listées
    // dynamiquement dans le footer plutôt que par deux liens en dur, pour
    // qu'en ajouter une (mentions légales...) n'exige aucun changement de
    // vue.
    function legal_pages(): Collection
    {
        return LegalPage::query()->orderBy('title')->get();
    }
}

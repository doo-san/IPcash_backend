<?php

use App\Models\SiteContent;

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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

// Réglage global du site public (lien réseau social, lien de
// téléchargement, image) — voir config/site_settings.php pour la liste des
// clés, app/Support/helpers.php pour la lecture côté vue, et
// App\Filament\Pages\SiteSettingsPage pour l'écran d'édition.
class SiteSetting extends Model
{
    use HasUuids;

    protected $fillable = ['key', 'value'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

// Surcharge admin d'un texte défini dans config('site_content') — voir
// app/Support/site_content.php pour la fonction globale site_content()
// utilisée dans les vues, et App\Filament\Pages\SiteContentPage pour
// l'écran d'édition.
class SiteContent extends Model
{
    use HasUuids;

    protected $fillable = ['page', 'key', 'value'];
}

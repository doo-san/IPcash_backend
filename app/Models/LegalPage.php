<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

// Page légale simple (Conditions d'utilisation, Confidentialité...) —
// éditable depuis l'admin (App\Filament\Resources\LegalPageResource),
// affichée publiquement sur /legal/{slug} (routes/web.php) et listée dans
// le footer (voir legal_pages() dans app/Support/helpers.php).
class LegalPage extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'slug', 'content'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

// Messages soumis via le formulaire de contact public (voir routes/web.php,
// ContactController) — pas de compte associé, l'auteur n'est jamais
// authentifié.
class ContactMessage extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'email', 'subject', 'message'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}

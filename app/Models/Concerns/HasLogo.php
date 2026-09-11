<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

// Partagé par les catalogues avec un logo téléversable depuis l'admin
// (`FileUpload` sur le disque "public", voir chaque Resource Filament).
// La colonne `logo_url` ne stocke que le chemin relatif (comportement
// natif de `FileUpload`) — cet accesseur reconstruit l'URL absolue
// attendue par l'app mobile (`logoUrl` dans les schémas openapi.yaml).
trait HasLogo
{
    public function logoUrl(): ?string
    {
        return $this->logo_url ? Storage::disk('public')->url($this->logo_url) : null;
    }
}

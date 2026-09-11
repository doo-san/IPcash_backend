<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

// `User` = compte staff/admin (connexion email + mot de passe au panneau
// Filament), distinct du client mobile IPCash (téléphone + PIN, voir
// `Account` côté Flutter) qui aura son propre modèle une fois le schéma
// métier mis en place.
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    // Sans cette méthode, Filament n'autorise l'accès au panneau qu'en
    // environnement `local` (garde-fou par défaut) — ce qui bloquerait
    // aussi bien les tests que la production une fois déployée. Tout
    // compte de la table `users` est un membre du staff (aucun client
    // mobile n'y figure jamais) : un compte qui existe ici a le droit
    // d'entrer, les rôles/permissions filtrent ensuite ce qu'il peut y
    // faire (ressources, actions).
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

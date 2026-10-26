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

    // Tout compte de la table `users` est un membre du staff (aucun client
    // mobile n'y figure jamais), mais un compte créé sans rôle (erreur
    // d'admin, script, future intégration SSO) ne doit pas pouvoir entrer
    // dans le panneau pour autant : il faut explicitement `super_admin` ou
    // `support` (voir `RoleSeeder`). Les rôles filtrent ensuite ce qu'un
    // compte peut y faire une fois dans le panneau (voir les `->visible()`
    // sur les actions qui déplacent de l'argent ou décident du KYC).
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['super_admin', 'support']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
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

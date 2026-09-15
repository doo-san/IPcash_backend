<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

/**
 * Écran de connexion : carte simple centrée sur un fond dégradé aux
 * couleurs IPCash, plutôt que la carte grise par défaut de Filament — vue
 * et layout personnalisés, cf. resources/views/filament/pages/auth/.
 */
class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

    protected static string $layout = 'filament.pages.auth.login-layout';
}

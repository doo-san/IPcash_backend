@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    {{--
        Écran de connexion volontairement toujours clair, quel que soit le
        thème système/local du visiteur : on retire la classe `dark` avant
        que le reste du body ne s'affiche, pour éviter le flash sombre posé
        par le script `loadDarkMode()` de layout.base. Ce même script se
        réabonne à `livewire:navigated` — qui se déclenche aussi au tout
        premier chargement une fois Livewire initialisé — et réappliquerait
        `dark` juste après notre retrait initial sans un second retrait sur
        le même évènement, enregistré après le sien pour gagner l'ordre
        d'exécution.
    --}}
    <script>
        const ipForceLightLogin = () => document.documentElement.classList.remove('dark');
        ipForceLightLogin();
        document.addEventListener('livewire:navigated', ipForceLightLogin);
    </script>
    <div class="ip-login">
        <div class="ip-login-card">
            <img src="{{ asset('images/ipcash-icon.svg') }}" alt="IPCash" class="ip-login-mark">
            {{ $slot }}
        </div>
    </div>
</x-filament-panels::layout.base>

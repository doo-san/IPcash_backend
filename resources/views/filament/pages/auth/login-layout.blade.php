@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="ip-login">
        <div class="ip-login-aside">
            <div class="ip-login-aside-inner">
                <img src="{{ asset('images/ipcash-logo-dark.svg') }}" alt="IPCash" class="ip-login-logo">
                <h2>Gérez IPCash avec la même rigueur que vos clients attendent de vous.</h2>
                <ul class="ip-login-points">
                    <li>
                        <span class="ip-login-dot"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                        Suivi des comptes et de la vérification KYC en temps réel
                    </li>
                    <li>
                        <span class="ip-login-dot"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8Z"/><path d="M16 13h2"/></svg></span>
                        Argent, mobile money et transactions sous contrôle
                    </li>
                    <li>
                        <span class="ip-login-dot"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg></span>
                        Accès sécurisé, journalisé et révocable à tout moment
                    </li>
                </ul>
            </div>
        </div>

        <div class="ip-login-main">
            <div class="ip-login-card">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-filament-panels::layout.base>

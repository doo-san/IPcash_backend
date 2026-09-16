<header class="site">
    <div class="wrap">
        <a href="{{ route('site.home') }}" class="logo">
            <img src="{{ asset('images/ipcash-icon.svg') }}" alt="" class="logo-mark">
            IPCash
        </a>
        <nav class="main">
            <a href="{{ route('site.home') }}" class="{{ request()->routeIs('site.home') ? 'active' : '' }}">Accueil</a>
            <a href="{{ route('site.features') }}" class="{{ request()->routeIs('site.features') ? 'active' : '' }}">Fonctionnalités</a>
            <a href="{{ route('site.security') }}" class="{{ request()->routeIs('site.security') ? 'active' : '' }}">Sécurité</a>
            <a href="{{ route('site.about') }}" class="{{ request()->routeIs('site.about') ? 'active' : '' }}">À propos</a>
            <a href="{{ route('site.contact') }}" class="{{ request()->routeIs('site.contact') ? 'active' : '' }}">Contact</a>
        </nav>
        <div class="header-actions">
            <div class="download-dropdown">
                <button type="button" class="btn btn-primary btn-sm" id="downloadToggle" aria-haspopup="true" aria-expanded="false" aria-controls="downloadMenu">Télécharger</button>
                <div class="download-menu" id="downloadMenu" hidden>
                    <a href="{{ route('site.home') }}#telecharger" aria-label="App Store">
                        <svg width="20" height="20" viewBox="0 0 384 512" fill="#fff"><path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/></svg>
                    </a>
                    <a href="{{ route('site.home') }}#telecharger" aria-label="Google Play">
                        <svg width="18" height="18" viewBox="0 0 24 24">
                            <defs>
                                <linearGradient id="playGradHeader" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#00D4FF"/>
                                    <stop offset="35%" stop-color="#3BE87E"/>
                                    <stop offset="65%" stop-color="#FFD400"/>
                                    <stop offset="100%" stop-color="#FF3B30"/>
                                </linearGradient>
                            </defs>
                            <path d="M5 3L19 12L5 21V3Z" fill="url(#playGradHeader)"/>
                        </svg>
                    </a>
                </div>
            </div>
            <a href="/admin" class="login-link">Se connecter</a>
        </div>
        <button class="nav-toggle" type="button" aria-label="Ouvrir le menu" onclick="document.getElementById('mobileNav').classList.toggle('open')">
            <span></span><span></span><span></span>
        </button>
    </div>
    <div class="mobile-nav" id="mobileNav">
        <a href="{{ route('site.home') }}">Accueil</a>
        <a href="{{ route('site.features') }}">Fonctionnalités</a>
        <a href="{{ route('site.security') }}">Sécurité</a>
        <a href="{{ route('site.about') }}">À propos</a>
        <a href="{{ route('site.contact') }}">Contact</a>
        <a href="/admin">Se connecter</a>
    </div>
</header>

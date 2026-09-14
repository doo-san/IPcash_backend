<header class="site">
    <div class="wrap">
        <a href="{{ route('site.home') }}" class="logo">
            <span class="logo-mark"></span>
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
            <a href="/admin" class="login-link">Se connecter</a>
            <a href="{{ route('site.home') }}#telecharger" class="btn btn-primary btn-sm">Télécharger</a>
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

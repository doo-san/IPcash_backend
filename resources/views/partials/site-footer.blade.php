<footer class="site-footer">
    <div class="wrap">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="{{ route('site.home') }}" class="logo"><img src="{{ asset('images/ipcash-icon.svg') }}" alt="" class="logo-mark">IPCash</a>
                <p>La super-app financière pensée pour le Sénégal et l'UEMOA — transférez, épargnez et payez depuis une seule application.</p>
                <div class="footer-social">
                    <a href="#" aria-label="LinkedIn"><svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5ZM3 9h4v12H3V9Zm7 0h3.8v1.7h.05c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.78 2.65 4.78 6.1V21h-4v-5.7c0-1.36-.03-3.1-1.9-3.1-1.9 0-2.2 1.48-2.2 3v5.8h-4V9Z"/></svg></a>
                    <a href="#" aria-label="Instagram"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1"/></svg></a>
                    <a href="#" aria-label="X"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 2H22l-7.6 8.7L23 22h-6.9l-5.4-6.6L4.5 22H1.4l8.1-9.3L1 2h7.1l4.9 6.1L18.9 2Zm-1.2 18h1.9L7.4 4H5.4l12.3 16Z"/></svg></a>
                    <a href="#" aria-label="Facebook"><svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M14 21v-8h2.7l.4-3.4H14V7.4c0-1 .3-1.6 1.7-1.6H17V2.8C16.7 2.7 15.8 2.6 14.7 2.6c-2.3 0-3.9 1.4-3.9 4v2.9H8v3.4h2.8v8h3.2Z"/></svg></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Produit</h4>
                <ul>
                    <li><a href="{{ route('site.features') }}">Fonctionnalités</a></li>
                    <li><a href="{{ route('site.security') }}">Sécurité</a></li>
                    <li><a href="{{ route('site.home') }}#faq">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Entreprise</h4>
                <ul>
                    <li><a href="{{ route('site.about') }}">À propos</a></li>
                    <li><a href="{{ route('site.contact') }}">Contact</a></li>
                    <li><a href="/admin">Se connecter</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Télécharger</h4>
                <ul>
                    <li><a href="{{ route('site.home') }}#telecharger">App Store</a></li>
                    <li><a href="{{ route('site.home') }}#telecharger">Google Play</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Légal</h4>
                <ul>
                    <li><a href="#">Conditions d'utilisation</a></li>
                    <li><a href="#">Confidentialité</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© {{ date('Y') }} IPCash. Tous droits réservés.</p>
            <p class="footer-tagline">Fait avec soin au Sénégal 🇸🇳</p>
        </div>
    </div>
</footer>

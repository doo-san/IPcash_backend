<footer>
    <div class="wrap">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="{{ route('site.home') }}" class="logo"><span class="logo-mark"></span>IPCash</a>
                <p>La super-app financière pensée pour le Sénégal et l'UEMOA.</p>
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
                    <li><a href="/admin">Espace administrateur</a></li>
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
        </div>
    </div>
</footer>

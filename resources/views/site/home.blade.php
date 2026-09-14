<x-site-layout :title="'Accueil'">

    <section class="hero" style="position:relative;overflow:hidden;padding:56px 0 40px;">
        <div style="content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 20%, rgba(62,220,180,0.22), transparent 55%), radial-gradient(circle at 80% 0%, rgba(58,76,242,0.16), transparent 50%);z-index:-1;pointer-events:none;"></div>
        <div class="wrap">
            <div style="display:grid;grid-template-columns:1.05fr 0.95fr;gap:40px;align-items:center;" class="hero-grid">
                <div class="reveal">
                    <h1 style="font-size:58px;line-height:1.08;margin:0 0 22px;">Votre argent,<br><span style="background:linear-gradient(90deg,var(--hero-start),var(--hero-end));-webkit-background-clip:text;background-clip:text;color:transparent;">enfin simple.</span></h1>
                    <p style="font-size:19px;color:var(--slate);max-width:480px;margin:0 0 36px;font-family:var(--sans);">
                        Transférez, épargnez, payez vos factures et changez de devise —
                        tout depuis une seule application, sans passer par une agence.
                    </p>
                    <x-store-badges />
                </div>
                <div class="reveal-scale" style="display:flex;justify-content:center;position:relative;">
                    <div style="width:280px;border-radius:42px;background:var(--ink);padding:12px;box-shadow:0 40px 80px -30px rgba(14,26,22,0.45),0 10px 24px -12px rgba(14,26,22,0.25);transform:rotate(2deg);">
                        <img src="{{ asset('images/screenshots/dashboard.png') }}" alt="Tableau de bord IPCash" style="display:block;width:100%;border-radius:32px;">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <style>@media (max-width: 920px) { .hero-grid { grid-template-columns: 1fr !important; } }
    @media (max-width: 640px) { .hero h1 { font-size: 36px !important; } }</style>

    <div class="wrap">
        <div class="stat-strip reveal" style="border-radius:var(--radius-md);border:1px solid var(--line);margin-top:8px;">
            <div><div class="num">8</div><div class="lbl">Services financiers</div></div>
            <div><div class="num">2</div><div class="lbl">Opérateurs mobile money</div></div>
            <div><div class="num">100%</div><div class="lbl">Chiffré de bout en bout</div></div>
            <div><div class="num">24/7</div><div class="lbl">Accessible depuis l'app</div></div>
        </div>
    </div>

    <div class="wrap reveal partner-strip" style="padding-top:56px;padding-bottom:8px;">
        <p style="text-align:center;font-size:13px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 24px;">Compatible avec vos opérateurs mobile money</p>
        <div style="display:flex;align-items:center;justify-content:center;gap:56px;flex-wrap:wrap;opacity:0.85;">
            <img src="{{ asset('images/partners/orange_money.svg') }}" alt="Orange Money" style="height:34px;width:auto;">
            <img src="{{ asset('images/partners/wave.svg') }}" alt="Wave" style="height:30px;width:auto;">
            <img src="{{ asset('images/partners/mixx_by_yas.svg') }}" alt="Mixx by Yas" style="height:30px;width:auto;">
        </div>
    </div>

    <section id="fonctionnalites">
        <div class="wrap">
            <div class="section-head center reveal">
                <h2>Tout ce dont vous avez besoin, au quotidien</h2>
                <p>Une seule app pour gérer votre argent, sans jongler entre plusieurs opérateurs.</p>
            </div>
            <div class="bento">
                <div class="card span-2 row-2 reveal">
                    <div class="icon">💸</div>
                    <h3>Transfert instantané</h3>
                    <p>Envoyez de l'argent à un proche en quelques secondes, sans frais cachés — vers un contact IPCash ou par numéro de téléphone.</p>
                </div>
                <div class="card reveal"><div class="icon">📲</div><h3>Mobile money</h3><p>Déposez et retirez depuis Orange Money ou Wave.</p></div>
                <div class="card reveal"><div class="icon">💱</div><h3>IPchange</h3><p>Détenez et échangez plusieurs devises au meilleur taux.</p></div>
                <div class="card reveal"><div class="icon">💳</div><h3>Carte virtuelle</h3><p>Une carte prépayée pour payer en ligne en sécurité.</p></div>
                <div class="card reveal"><div class="icon">🏺</div><h3>Poches d'épargne</h3><p>Mettez de côté pour vos projets, à votre rythme.</p></div>
                <div class="card span-2 reveal"><div class="icon">🧾</div><h3>Factures &amp; QR</h3><p>Payez eau, électricité, Canal+ et vos achats du quotidien en scannant un simple code QR.</p></div>
                <div class="card reveal"><div class="icon">📶</div><h3>Crédit &amp; eSIM</h3><p>Rechargez votre forfait ou activez un eSIM voyage.</p></div>
                <div class="card reveal"><div class="icon">🛡️</div><h3>Assurance</h3><p>Souscrivez une assurance auto depuis votre compte.</p></div>
            </div>
            <p class="reveal" style="text-align:center;margin-top:40px;">
                <a href="{{ route('site.features') }}" class="btn btn-ghost">Voir toutes les fonctionnalités en détail →</a>
            </p>
        </div>
    </section>

    <section class="alt">
        <div class="wrap">
            <div class="section-head center reveal">
                <h2>Trois étapes, et c'est fait</h2>
                <p>Pas de dossier, pas de rendez-vous en agence.</p>
            </div>
            <div class="steps">
                <div class="step reveal">
                    <div class="num" style="background:var(--hero-start);">1</div>
                    <h3>Créez votre compte</h3>
                    <p>Votre numéro de téléphone, une vérification d'identité rapide, et c'est parti.</p>
                </div>
                <div class="step reveal">
                    <div class="num" style="background:var(--hero-end);">2</div>
                    <h3>Approvisionnez votre solde</h3>
                    <p>Depuis Orange Money, Wave, ou tout autre moyen disponible dans l'app.</p>
                </div>
                <div class="step reveal">
                    <div class="num" style="background:var(--sun);">3</div>
                    <h3>Utilisez votre argent</h3>
                    <p>Transférez, épargnez, payez — tout devient possible en quelques taps.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="dark" style="background-image:linear-gradient(rgba(5,10,25,.3),rgba(5,10,25,.3)),url('{{ asset('images/backgrounds/security-section.jpeg') }}');background-size:cover;background-position:center;">
        <div class="wrap">
            <div class="section-head center reveal">
                <h2>Votre sécurité, notre priorité</h2>
                <p>Votre argent et vos données sont protégés à chaque étape.</p>
            </div>
            <div class="security-grid">
                <div class="security-item reveal"><div class="icon">🔒</div><div><h3>Code secret &amp; biométrie</h3><p>Chaque opération sensible est confirmée par votre PIN ou Face ID.</p></div></div>
                <div class="security-item reveal"><div class="icon">🪪</div><div><h3>Identité vérifiée</h3><p>Vérification complète avant tout accès à votre compte.</p></div></div>
                <div class="security-item reveal"><div class="icon">📡</div><div><h3>Sessions sous contrôle</h3><p>Consultez et révoquez les appareils connectés à tout moment.</p></div></div>
                <div class="security-item reveal"><div class="icon">🚫</div><div><h3>Blocage à distance</h3><p>Bloquez votre compte immédiatement en cas de perte ou de vol.</p></div></div>
            </div>
            <p class="reveal" style="text-align:center;margin-top:36px;">
                <a href="{{ route('site.security') }}" class="btn btn-ghost">En savoir plus sur la sécurité →</a>
            </p>
        </div>
    </section>

    <section id="faq">
        <div class="wrap">
            <div class="section-head center reveal">
                <h2>Tout ce qu'il faut savoir</h2>
            </div>
            <div class="wrap-narrow reveal" style="padding:0;">
                <details class="faq-item" open>
                    <summary>IPCash est-il gratuit ?<span class="plus"></span></summary>
                    <p>L'ouverture de compte et l'application sont gratuites. Certaines opérations (dépôt/retrait mobile money, conversion de devise) peuvent comporter des frais, toujours affichés avant confirmation.</p>
                </details>
                <details class="faq-item">
                    <summary>Comment vérifier mon identité ?<span class="plus"></span></summary>
                    <p>À l'inscription, vous photographiez une pièce d'identité et prenez un selfie. La vérification est nécessaire avant tout mouvement d'argent.</p>
                </details>
                <details class="faq-item">
                    <summary>Quels opérateurs mobile money sont supportés ?<span class="plus"></span></summary>
                    <p>Orange Money et Wave sont pris en charge pour les dépôts et retraits, directement depuis l'application.</p>
                </details>
                <details class="faq-item">
                    <summary>Que faire si je perds mon téléphone ?<span class="plus"></span></summary>
                    <p>Bloquez votre compte immédiatement depuis un autre appareil ou contactez le support — aucune opération ne sera possible tant que le blocage est actif.</p>
                </details>
            </div>
        </div>
    </section>

    <section id="telecharger" class="tight">
        <div class="wrap">
            <div class="cta-band reveal-scale">
                <h2>Prêt à simplifier votre argent ?</h2>
                <p>IPCash arrive bientôt sur l'App Store et Google Play.</p>
                <x-store-badges class="center" />
            </div>
        </div>
    </section>

</x-site-layout>

<x-site-layout :title="site_setting('seo_home_title')" :description="site_setting('seo_home_description')">

    <section class="hero" style="position:relative;overflow:hidden;padding:56px 0 40px;">
        <div style="content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 20%, rgba(62,220,180,0.22), transparent 55%), radial-gradient(circle at 80% 0%, rgba(58,76,242,0.16), transparent 50%);z-index:-1;pointer-events:none;"></div>
        <div class="wrap">
            <div style="display:grid;grid-template-columns:1.05fr 0.95fr;gap:40px;align-items:center;" class="hero-grid">
                <div class="reveal">
                    <h1 style="font-size:58px;line-height:1.08;margin:0 0 22px;">Votre argent,<br><span style="background:linear-gradient(90deg,var(--hero-start),var(--hero-end));-webkit-background-clip:text;background-clip:text;color:transparent;">enfin simple.</span></h1>
                    <p style="font-size:19px;color:var(--slate);max-width:480px;margin:0 0 36px;font-family:var(--sans);">
                        {{ site_content('home', 'hero_subtitle') }}
                    </p>
                    <x-store-badges />
                </div>
                <div class="reveal-scale" style="display:flex;justify-content:center;position:relative;">
                    <div style="width:280px;border-radius:42px;background:var(--ink);padding:12px;box-shadow:0 40px 80px -30px rgba(14,26,22,0.45),0 10px 24px -12px rgba(14,26,22,0.25);transform:rotate(2deg);">
                        <img src="{{ site_setting_image_url('hero_screenshot') }}" alt="Tableau de bord IPCash" style="display:block;width:100%;border-radius:32px;">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <style>@media (max-width: 920px) { .hero-grid { grid-template-columns: 1fr !important; } }
    @media (max-width: 640px) { .hero h1 { font-size: 36px !important; } }</style>

    <div class="wrap reveal partner-strip" style="padding-top:40px;padding-bottom:8px;">
        <div style="display:flex;align-items:center;justify-content:center;gap:56px;flex-wrap:wrap;opacity:0.85;">
            <img src="{{ site_setting_image_url('partner_orange_money') }}" alt="Orange Money" style="height:46px;width:auto;">
            <img src="{{ site_setting_image_url('partner_wave') }}" alt="Wave" style="height:42px;width:auto;">
            <img src="{{ site_setting_image_url('partner_mixx_by_yas') }}" alt="Mixx by Yas" style="height:42px;width:auto;">
        </div>
    </div>

    <section id="fonctionnalites">
        <div class="wrap">
            <div class="section-head center reveal">
                <h2>{{ site_content('home', 'features_heading') }}</h2>
            </div>
            @php
                $icons = [
                    'swap' => '<path d="M7 7h11l-3-3M18 7l-3 3"/><path d="M17 17H6l3 3M6 17l3-3"/>',
                    'phone' => '<rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 18h2"/>',
                    'exchange' => '<path d="M4 12a8 8 0 0 1 14-5l2 2"/><path d="M20 5v4h-4"/><path d="M20 12a8 8 0 0 1-14 5l-2-2"/><path d="M4 19v-4h4"/>',
                    'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/>',
                    'wallet' => '<path d="M3 8a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8Z"/><path d="M16 13h2"/><path d="M3 8V6a2 2 0 0 1 2-2h9"/>',
                    'qr' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3m4 0h.01M14 18h7m-7 3h3m4-3v3"/>',
                    'signal' => '<path d="M4 18h.01M9 18v-4M14 18v-8M19 18V6"/>',
                    'shield' => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3Z"/><path d="M9 12l2 2 4-4"/>',
                ];
                $iconSvg = fn (string $key) => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$icons[$key].'</svg>';
            @endphp
            <div class="bento">
                <div class="card span-2 row-2 reveal">
                    <div class="icon" style="background:var(--green);">{!! $iconSvg('swap') !!}</div>
                    <h3>Transfert instantané</h3>
                    <p>Envoyez de l'argent à un proche en quelques secondes, sans frais cachés — vers un contact IPCash ou par numéro de téléphone.</p>
                </div>
                <div class="card reveal"><div class="icon" style="background:var(--hero-end);">{!! $iconSvg('phone') !!}</div><h3>Mobile money</h3><p>Déposez et retirez depuis Orange Money ou Wave.</p></div>
                <div class="card reveal"><div class="icon" style="background:var(--sun);">{!! $iconSvg('exchange') !!}</div><h3>IPchange</h3><p>Détenez et échangez plusieurs devises au meilleur taux.</p></div>
                <div class="card reveal"><div class="icon" style="background:var(--pink);">{!! $iconSvg('card') !!}</div><h3>Carte virtuelle</h3><p>Une carte prépayée pour payer en ligne en sécurité.</p></div>
                <div class="card reveal"><div class="icon" style="background:var(--green);">{!! $iconSvg('wallet') !!}</div><h3>Poches d'épargne</h3><p>Mettez de côté pour vos projets, à votre rythme.</p></div>
                <div class="card span-2 reveal"><div class="icon" style="background:var(--hero-end);">{!! $iconSvg('qr') !!}</div><h3>Factures &amp; QR</h3><p>Payez eau, électricité, Canal+ et vos achats du quotidien en scannant un simple code QR.</p></div>
                <div class="card reveal"><div class="icon" style="background:var(--sun);">{!! $iconSvg('signal') !!}</div><h3>Crédit &amp; eSIM</h3><p>Rechargez votre forfait ou activez un eSIM voyage.</p></div>
                <div class="card reveal"><div class="icon" style="background:var(--pink);">{!! $iconSvg('shield') !!}</div><h3>Assurance</h3><p>Souscrivez une assurance auto depuis votre compte.</p></div>
            </div>
            <p class="reveal" style="text-align:center;margin-top:44px;">
                <a href="{{ route('site.features') }}" class="btn btn-primary">
                    Voir toutes les fonctionnalités
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </p>
        </div>
    </section>

    <section class="alt">
        <div class="wrap">
            <div class="section-head center reveal">
                <h2>{{ site_content('home', 'steps_heading') }}</h2>
                <p>{{ site_content('home', 'steps_subheading') }}</p>
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

    <section class="dark" style="background-image:linear-gradient(rgba(5,10,25,.3),rgba(5,10,25,.3)),url('{{ site_setting_image_url('security_background') }}');background-size:cover;background-position:center;">
        <div class="wrap">
            <div class="section-head center reveal">
                <h2>{{ site_content('home', 'security_heading') }}</h2>
                <p>{{ site_content('home', 'security_subheading') }}</p>
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
                <h2>{{ site_content('home', 'faq_heading') }}</h2>
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
                <h2>{{ site_content('home', 'cta_heading') }}</h2>
                <p>{{ site_content('home', 'cta_subheading') }}</p>
                <x-store-badges class="center" />
            </div>
        </div>
    </section>

</x-site-layout>

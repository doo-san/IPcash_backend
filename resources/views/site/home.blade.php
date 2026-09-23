<x-site-layout :title="site_setting('seo_home_title')" :description="site_setting('seo_home_description')">

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
        $iconSvg = fn (string $key, int $size = 22) => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'.$icons[$key].'</svg>';
    @endphp

    {{-- Hero sobre : titre centré, un seul CTA, grande capture d'écran — pas de mesh coloré ni de gadget flottant --}}
    <section style="padding:76px 0 0;">
        <div class="wrap reveal" style="max-width:700px;margin:0 auto;text-align:center;">
            <h1 style="font-size:50px;line-height:1.12;margin:0 0 20px;">Votre argent,<br><span class="grad-text">enfin simple.</span></h1>
            <p style="font-size:18px;color:var(--slate);margin:0 0 32px;font-family:var(--sans);">
                {{ site_content('home', 'hero_subtitle') }}
            </p>
            <x-store-badges class="center" />
        </div>
        <div class="wrap reveal-scale" style="margin-top:52px;">
            <div style="max-width:300px;margin:0 auto;border-radius:36px;overflow:hidden;border:1px solid var(--line);box-shadow:0 30px 70px -30px rgba(14,26,22,0.35);">
                <img src="{{ site_setting_image_url('hero_screenshot') }}" alt="Tableau de bord IPCash" style="display:block;width:100%;">
            </div>
        </div>
    </section>

    <div class="wrap reveal" style="max-width:600px;margin:0 auto;text-align:center;padding:44px 24px 0;">
        <p style="color:var(--slate);font-size:15px;font-weight:600;margin:0;">
            🔒 Vos transactions sont chiffrées et protégées de bout en bout —
            <a href="{{ route('site.security') }}" style="color:var(--green-deep);font-weight:700;text-decoration:none;">en savoir plus</a>
        </p>
    </div>

    <div class="wrap reveal partner-strip" style="padding-top:40px;padding-bottom:8px;">
        <p style="text-align:center;font-size:12.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--slate-light);margin:0 0 24px;">Compatible avec</p>
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

            <div class="deep-dive" id="transfert">
                <div class="reveal">
                    <span class="feature-tag" style="background:rgba(0,160,91,.12);color:var(--green-deep);">Transfert</span>
                    <h3>Envoyez et recevez de l'argent en un instant</h3>
                    <p class="desc">Choisissez un contact IPCash ou saisissez un numéro de téléphone : l'argent arrive immédiatement, confirmé par votre code PIN ou votre empreinte — sans frais entre comptes IPCash.</p>
                    <a href="{{ route('site.features') }}#transfert" class="btn btn-ghost">
                        Comment ça marche
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>
                <div class="deep-visual grad-1 reveal-scale"><span class="big-icon-badge" style="background:var(--green);">{!! $iconSvg('swap', 44) !!}</span></div>
            </div>

            <div class="compact-grid" style="margin-top:56px;">
                <div class="compact-card reveal">
                    <div class="icon" style="background:var(--hero-end);">{!! $iconSvg('phone', 20) !!}</div>
                    <div><h3>Mobile money</h3><p>Déposez et retirez depuis Orange Money ou Wave.</p></div>
                </div>
                <div class="compact-card reveal">
                    <div class="icon" style="background:var(--sun);">{!! $iconSvg('exchange', 20) !!}</div>
                    <div><h3>IPchange</h3><p>Détenez et échangez plusieurs devises au meilleur taux.</p></div>
                </div>
                <div class="compact-card reveal">
                    <div class="icon" style="background:var(--pink);">{!! $iconSvg('card', 20) !!}</div>
                    <div><h3>Carte virtuelle</h3><p>Une carte prépayée pour payer en ligne en sécurité.</p></div>
                </div>
                <div class="compact-card reveal">
                    <div class="icon" style="background:var(--green);">{!! $iconSvg('wallet', 20) !!}</div>
                    <div><h3>Poches d'épargne</h3><p>Mettez de côté pour vos projets, à votre rythme.</p></div>
                </div>
                <div class="compact-card reveal">
                    <div class="icon" style="background:var(--hero-end);">{!! $iconSvg('qr', 20) !!}</div>
                    <div><h3>Factures &amp; QR</h3><p>Eau, électricité, Canal+ et paiements par simple scan.</p></div>
                </div>
                <div class="compact-card reveal">
                    <div class="icon" style="background:var(--sun);">{!! $iconSvg('signal', 20) !!}</div>
                    <div><h3>Crédit &amp; eSIM</h3><p>Rechargez votre forfait ou activez un eSIM voyage.</p></div>
                </div>
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

    <section>
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

    <section class="alt" id="faq">
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

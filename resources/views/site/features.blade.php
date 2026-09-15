<x-site-layout :title="'Fonctionnalités'" :description="'Toutes les fonctionnalités IPCash en détail : transfert, mobile money, IPchange, carte virtuelle, épargne, factures, crédit, eSIM et assurance.'">

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
        $sections = [
            ['id' => 'transfert', 'label' => 'Transfert', 'icon' => 'swap', 'color' => 'var(--green)'],
            ['id' => 'mobile-money', 'label' => 'Mobile money', 'icon' => 'phone', 'color' => 'var(--hero-end)'],
            ['id' => 'ipchange', 'label' => 'IPchange', 'icon' => 'exchange', 'color' => 'var(--sun)'],
            ['id' => 'carte', 'label' => 'Carte', 'icon' => 'card', 'color' => 'var(--pink)'],
            ['id' => 'epargne', 'label' => 'Épargne', 'icon' => 'wallet', 'color' => 'var(--green)'],
            ['id' => 'factures', 'label' => 'Factures & QR', 'icon' => 'qr', 'color' => 'var(--hero-end)'],
            ['id' => 'credit-esim', 'label' => 'Crédit & eSIM', 'icon' => 'signal', 'color' => 'var(--sun)'],
            ['id' => 'assurance', 'label' => 'Assurance', 'icon' => 'shield', 'color' => 'var(--pink)'],
        ];
    @endphp

    <section class="page-hero" style="padding-bottom:32px;">
        <div class="wrap reveal">
            <span class="kicker">Fonctionnalités</span>
            <h1>Une app, huit façons de simplifier votre argent.</h1>
            <p class="lede">Du transfert instantané à l'assurance auto, chaque service IPCash est pensé pour remplacer une file d'attente par quelques secondes sur votre téléphone.</p>
        </div>
    </section>

    <div class="wrap reveal">
        <nav class="feature-nav">
            @foreach ($sections as $s)
                <a href="#{{ $s['id'] }}">
                    <span class="feature-nav-icon" style="background:{{ $s['color'] }};">{!! $iconSvg($s['icon'], 16) !!}</span>
                    {{ $s['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    <section class="tight">
        <div class="wrap">

            <div class="deep-dive" id="transfert">
                <div class="reveal">
                    <span class="kicker">Transfert P2P</span>
                    <h3>Envoyez de l'argent en quelques secondes</h3>
                    <p class="desc">Choisissez un contact IPCash ou saisissez un numéro de téléphone : l'argent arrive instantanément, confirmé par votre code PIN ou votre empreinte.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Aucun frais entre comptes IPCash</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Historique complet et reçu partageable</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Confirmation par PIN ou biométrie</li>
                    </ul>
                </div>
                <div class="deep-visual grad-1 reveal-scale"><span class="big-icon-badge" style="background:var(--green);">{!! $iconSvg('swap', 44) !!}</span></div>
            </div>

            <div class="deep-dive reverse" id="mobile-money">
                <div class="reveal">
                    <span class="kicker">Mobile money</span>
                    <h3>Dépôts et retraits Orange Money &amp; Wave</h3>
                    <p class="desc">Rechargez votre solde IPCash depuis votre compte Orange Money ou Wave, ou retirez vers l'un de ces opérateurs — sans passer par un point de vente.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#EAF0FF"/><path d="M8 12l2.5 2.5L16 9" stroke="#1E3A9E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Confirmation directe depuis votre app mobile money</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#EAF0FF"/><path d="M8 12l2.5 2.5L16 9" stroke="#1E3A9E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Suivi en temps réel du statut</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#EAF0FF"/><path d="M8 12l2.5 2.5L16 9" stroke="#1E3A9E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Frais annoncés avant confirmation</li>
                    </ul>
                </div>
                <div class="deep-visual grad-3 reveal-scale"><span class="big-icon-badge" style="background:var(--hero-end);">{!! $iconSvg('phone', 44) !!}</span></div>
            </div>

            <div class="deep-dive" id="ipchange">
                <div class="reveal">
                    <span class="kicker">IPchange</span>
                    <h3>Vos devises, sous contrôle</h3>
                    <p class="desc">Ouvrez des sous-comptes en devises étrangères, convertissez à tout moment au meilleur taux et envoyez directement en devise à l'international.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Taux transparent, affiché avant conversion</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Plusieurs devises détenues simultanément</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Conversion instantanée, 24h/24</li>
                    </ul>
                </div>
                <div class="deep-visual grad-1 reveal-scale"><span class="big-icon-badge" style="background:var(--sun);">{!! $iconSvg('exchange', 44) !!}</span></div>
            </div>

            <div class="deep-dive reverse" id="carte">
                <div class="reveal">
                    <span class="kicker">Carte virtuelle</span>
                    <h3>Payez en ligne, sans exposer votre carte réelle</h3>
                    <p class="desc">Générez une carte prépayée en un instant pour vos achats en ligne. Rechargez-la depuis votre solde principal et gardez le contrôle total sur son plafond.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#FFF6E9"/><path d="M8 12l2.5 2.5L16 9" stroke="#8A5A00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Génération instantanée</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#FFF6E9"/><path d="M8 12l2.5 2.5L16 9" stroke="#8A5A00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Gel ou blocage à tout moment</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#FFF6E9"/><path d="M8 12l2.5 2.5L16 9" stroke="#8A5A00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Aucune donnée bancaire réelle exposée</li>
                    </ul>
                </div>
                <div class="deep-visual grad-2 reveal-scale"><span class="big-icon-badge" style="background:var(--pink);">{!! $iconSvg('card', 44) !!}</span></div>
            </div>

            <div class="deep-dive" id="epargne">
                <div class="reveal">
                    <span class="kicker">Poches d'épargne</span>
                    <h3>Mettez de l'argent de côté, sans y penser</h3>
                    <p class="desc">Créez des poches dédiées à vos projets — vacances, imprévu, achat — et suivez votre progression sans toucher à votre solde principal.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Autant de poches que nécessaire</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Transfert instantané vers/depuis le solde principal</li>
                    </ul>
                </div>
                <div class="deep-visual grad-4 reveal-scale"><span class="big-icon-badge" style="background:var(--green);">{!! $iconSvg('wallet', 44) !!}</span></div>
            </div>

            <div class="deep-dive reverse" id="factures">
                <div class="reveal">
                    <span class="kicker">Factures &amp; QR</span>
                    <h3>Fini la file d'attente pour payer vos factures</h3>
                    <p class="desc">Eau, électricité, Canal+ et bien plus — réglez vos factures récurrentes en quelques taps, ou scannez un code QR chez un commerçant partenaire.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#EAF0FF"/><path d="M8 12l2.5 2.5L16 9" stroke="#1E3A9E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Comptes de facturation enregistrés</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#EAF0FF"/><path d="M8 12l2.5 2.5L16 9" stroke="#1E3A9E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Paiement marchand par QR code</li>
                    </ul>
                </div>
                <div class="deep-visual grad-3 reveal-scale"><span class="big-icon-badge" style="background:var(--hero-end);">{!! $iconSvg('qr', 44) !!}</span></div>
            </div>

            <div class="deep-dive" id="credit-esim">
                <div class="reveal">
                    <span class="kicker">Crédit &amp; eSIM</span>
                    <h3>Restez connecté, où que vous soyez</h3>
                    <p class="desc">Rechargez votre forfait mobile en quelques secondes, ou activez un eSIM avant un voyage — sans passer par un point de vente physique.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#FFF6E9"/><path d="M8 12l2.5 2.5L16 9" stroke="#8A5A00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Recharge crédit instantanée</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#FFF6E9"/><path d="M8 12l2.5 2.5L16 9" stroke="#8A5A00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Forfaits eSIM voyage</li>
                    </ul>
                </div>
                <div class="deep-visual grad-2 reveal-scale"><span class="big-icon-badge" style="background:var(--sun);">{!! $iconSvg('signal', 44) !!}</span></div>
            </div>

            <div class="deep-dive reverse" id="assurance">
                <div class="reveal">
                    <span class="kicker">Assurance</span>
                    <h3>Votre assurance auto, sans paperasse</h3>
                    <p class="desc">Souscrivez une assurance auto directement depuis votre compte IPCash et gérez votre contrat sans vous déplacer.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Souscription en quelques minutes</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Paiement directement depuis votre solde</li>
                    </ul>
                </div>
                <div class="deep-visual grad-4 reveal-scale"><span class="big-icon-badge" style="background:var(--pink);">{!! $iconSvg('shield', 44) !!}</span></div>
            </div>

        </div>
    </section>

    <section class="tight">
        <div class="wrap">
            <div class="cta-band reveal-scale">
                <h2>Découvrez tout ça par vous-même</h2>
                <p>IPCash arrive bientôt sur l'App Store et Google Play.</p>
                <div class="cta-row">
                    <a href="{{ route('site.home') }}#telecharger" class="btn btn-primary">Télécharger l'app</a>
                    <a href="{{ route('site.security') }}" class="btn btn-ghost">Voir la sécurité</a>
                </div>
            </div>
        </div>
    </section>

</x-site-layout>

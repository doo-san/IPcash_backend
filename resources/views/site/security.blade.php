<x-site-layout :title="'Sécurité'" :description="'Comment IPCash protège votre argent et vos données : vérification d’identité, code PIN et biométrie, chiffrement, contrôle des sessions et blocage immédiat.'">

    @php
        $icons = [
            'lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
            'idcard' => '<rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M6 17c.5-2 2-3 3-3s2.5 1 3 3"/><path d="M14 9h4M14 13h4"/>',
            'wifi' => '<path d="M12 19h.01"/><path d="M8.5 15.5a5 5 0 0 1 7 0"/><path d="M5 12a10 10 0 0 1 14 0"/>',
            'block' => '<circle cx="12" cy="12" r="9"/><path d="M5.5 5.5l13 13"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'key' => '<circle cx="8" cy="15" r="3"/><path d="M10.4 12.6L18 5m0 0v4m0-4h-4"/>',
            'doc' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        ];
        $iconSvg = fn (string $key, int $size = 22) => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'.$icons[$key].'</svg>';
        $sections = [
            ['id' => 'acces', 'label' => 'Accès au compte', 'icon' => 'lock', 'color' => 'var(--green)'],
            ['id' => 'identite', 'label' => 'Identité vérifiée', 'icon' => 'idcard', 'color' => 'var(--hero-end)'],
            ['id' => 'sessions', 'label' => 'Sessions', 'icon' => 'wifi', 'color' => 'var(--sun)'],
            ['id' => 'blocage', 'label' => 'Blocage', 'icon' => 'block', 'color' => 'var(--pink)'],
        ];
    @endphp

    <section class="page-hero">
        <div class="wrap reveal">
            <span class="kicker">Sécurité</span>
            <h1>Conçu pour que vous gardiez toujours le contrôle.</h1>
            <p class="lede">Une néobanque se juge à la confiance qu'elle inspire. Voici, concrètement, comment votre argent et vos données sont protégés à chaque étape.</p>
        </div>
    </section>

    <div class="wrap reveal" style="margin-top:48px;">
        <div class="marquee-wrap">
            <div class="marquee-track">
                @for ($i = 0; $i < 2; $i++)
                    <nav class="marquee-group" @if ($i === 1) aria-hidden="true" @endif>
                        @foreach ($sections as $s)
                            <a href="#{{ $s['id'] }}">
                                <span class="feature-nav-icon" style="background:{{ $s['color'] }};">{!! $iconSvg($s['icon'], 16) !!}</span>
                                {{ $s['label'] }}
                            </a>
                        @endforeach
                    </nav>
                @endfor
            </div>
        </div>
    </div>

    <section class="tight">
        <div class="wrap">

            <div class="deep-dive" id="acces">
                <div class="reveal">
                    <span class="feature-tag" style="background:rgba(0,160,91,.12);color:var(--green-deep);">Accès au compte</span>
                    <h3>Code secret et biométrie à chaque opération sensible</h3>
                    <p class="desc">Un transfert, un retrait ou un changement de paramètre sensible demande toujours une confirmation — par votre code PIN à 6 chiffres ou par Face ID/empreinte digitale.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Code PIN jamais stocké en clair</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Verrouillage automatique après plusieurs échecs</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Déverrouillage biométrique optionnel</li>
                    </ul>
                </div>
                <div class="deep-visual grad-1 reveal-scale"><span class="big-icon-badge" style="background:var(--green);">{!! $iconSvg('lock', 44) !!}</span></div>
            </div>

            <div class="deep-dive reverse" id="identite">
                <div class="reveal">
                    <span class="feature-tag" style="background:rgba(58,76,242,.1);color:var(--hero-end);">Identité vérifiée</span>
                    <h3>Personne n'ouvre un compte sans être vérifié</h3>
                    <p class="desc">À l'inscription, chaque client photographie une pièce d'identité et prend un selfie. Aucun mouvement d'argent n'est possible tant que la vérification n'est pas validée.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#EAF0FF"/><path d="M8 12l2.5 2.5L16 9" stroke="#1E3A9E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Analyse du document et de la photo</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#EAF0FF"/><path d="M8 12l2.5 2.5L16 9" stroke="#1E3A9E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Revue avant toute validation</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#EAF0FF"/><path d="M8 12l2.5 2.5L16 9" stroke="#1E3A9E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Aucun accès au compte avant validation</li>
                    </ul>
                </div>
                <div class="deep-visual grad-3 reveal-scale"><span class="big-icon-badge" style="background:var(--hero-end);">{!! $iconSvg('idcard', 44) !!}</span></div>
            </div>

            <div class="deep-dive" id="sessions">
                <div class="reveal">
                    <span class="feature-tag" style="background:rgba(255,182,72,.18);color:#8A5A00;">Appareils &amp; sessions</span>
                    <h3>Voyez qui est connecté, et coupez l'accès en un geste</h3>
                    <p class="desc">Chaque connexion à votre compte est identifiée par appareil. Vous pouvez consulter la liste à tout moment et révoquer n'importe quelle session, y compris à distance.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#FFF6E9"/><path d="M8 12l2.5 2.5L16 9" stroke="#8A5A00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Liste des appareils connectés</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#FFF6E9"/><path d="M8 12l2.5 2.5L16 9" stroke="#8A5A00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Révocation immédiate d'une session</li>
                    </ul>
                </div>
                <div class="deep-visual grad-2 reveal-scale"><span class="big-icon-badge" style="background:var(--sun);">{!! $iconSvg('wifi', 44) !!}</span></div>
            </div>

            <div class="deep-dive reverse" id="blocage">
                <div class="reveal">
                    <span class="feature-tag" style="background:rgba(242,111,160,.14);color:#B23368;">En cas de perte ou de vol</span>
                    <h3>Un blocage immédiat, pas une procédure de trois jours</h3>
                    <p class="desc">Bloquez votre compte ou votre carte virtuelle en un instant. Un blocage administratif coupe tout accès — connexion, jetons déjà émis, et toute nouvelle opération.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Blocage définitif ou temporaire</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Carte virtuelle gelable en un tap</li>
                    </ul>
                </div>
                <div class="deep-visual grad-4 reveal-scale"><span class="big-icon-badge" style="background:var(--pink);">{!! $iconSvg('block', 44) !!}</span></div>
            </div>

        </div>
    </section>

    <section class="alt">
        <div class="wrap">
            <div class="section-head center reveal">
                <h2>Les principes qui guident chaque décision technique</h2>
            </div>
            <div class="bento" style="grid-auto-rows:170px;">
                <div class="card reveal"><div class="icon" style="background:var(--green);">{!! $iconSvg('lock', 20) !!}</div><h3>Rien en clair</h3><p>Code PIN, secrets d'intégration et documents sensibles sont chiffrés, jamais journalisés.</p></div>
                <div class="card reveal"><div class="icon" style="background:var(--hero-end);">{!! $iconSvg('clock', 20) !!}</div><h3>Pas de fausse réussite</h3><p>Une opération reste "en cours" tant qu'elle n'est pas confirmée — jamais affichée comme réussie par anticipation.</p></div>
                <div class="card reveal"><div class="icon" style="background:var(--sun);">{!! $iconSvg('key', 20) !!}</div><h3>Jamais deux fois</h3><p>Chaque opération porte une clé unique : impossible d'être débité deux fois pour une même action.</p></div>
                <div class="card reveal"><div class="icon" style="background:var(--pink);">{!! $iconSvg('doc', 20) !!}</div><h3>Tout est journalisé</h3><p>Chaque action administrative sur un compte est tracée et consultable.</p></div>
            </div>
        </div>
    </section>

    <section class="tight">
        <div class="wrap">
            <div class="cta-band reveal-scale">
                <h2>Des questions sur la sécurité de vos données ?</h2>
                <p>Notre équipe vous répond directement.</p>
                <div class="cta-row">
                    <a href="{{ route('site.contact') }}" class="btn btn-primary">Nous contacter</a>
                    <a href="{{ route('site.features') }}" class="btn btn-ghost">Voir les fonctionnalités</a>
                </div>
            </div>
        </div>
    </section>

</x-site-layout>

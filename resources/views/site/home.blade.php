<x-site-layout :title="'Accueil'">

    <section class="hero-centered">
        <div class="wrap reveal">
            <span class="pill"><span class="dot"></span> Pensé pour le Sénégal et l'UEMOA</span>
            <h1>Votre argent,<br><span style="background:linear-gradient(90deg,var(--hero-start),var(--hero-end));-webkit-background-clip:text;background-clip:text;color:transparent;">enfin simple.</span></h1>
            <p class="lede">
                Transférez, épargnez, payez vos factures et changez de devise —
                tout depuis une seule application, sans passer par une agence.
            </p>
            <div class="cta-row">
                <a href="#telecharger" class="btn btn-primary">Télécharger sur l'App Store</a>
                <a href="#telecharger" class="btn btn-ghost">Disponible sur Google Play</a>
            </div>
            <p class="cta-note">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4" stroke="#00A05B" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="10" stroke="#00A05B" stroke-width="1.6"/></svg>
                Bientôt disponible
            </p>
        </div>
    </section>

    <section class="tight" style="background:var(--mist);padding-top:0;">
        <div class="preview-stage reveal-scale">
            <div class="preview-glow"></div>
            <div class="preview-toast t1">🔒&nbsp; Compte vérifié</div>
            <div class="preview-toast t2">⚡&nbsp; Transfert instantané</div>
            <div class="preview-toast t3">💱&nbsp; 3 devises</div>
            <div class="preview-phone">
                <div class="screen">
                    <div style="height:30px;display:flex;align-items:flex-end;justify-content:center;padding-bottom:5px;"><span style="width:76px;height:5px;border-radius:999px;background:rgba(0,0,0,0.15);"></span></div>
                    <div style="margin:8px 16px 16px;padding:22px;border-radius:22px;color:#fff;background:linear-gradient(135deg,var(--hero-start),var(--hero-end));">
                        <small style="opacity:.8;font-size:12.5px;font-weight:600;">SOLDE DISPONIBLE</small>
                        <div style="font-size:30px;font-weight:800;margin-top:6px;letter-spacing:-0.02em;">248 500 F</div>
                    </div>
                    <div style="display:flex;gap:10px;padding:0 16px 18px;">
                        <span style="flex:1;text-align:center;font-size:11.5px;font-weight:700;color:var(--ink);background:var(--mist);border-radius:13px;padding:11px 4px;">Envoyer</span>
                        <span style="flex:1;text-align:center;font-size:11.5px;font-weight:700;color:var(--ink);background:var(--mist);border-radius:13px;padding:11px 4px;">Déposer</span>
                        <span style="flex:1;text-align:center;font-size:11.5px;font-weight:700;color:var(--ink);background:var(--mist);border-radius:13px;padding:11px 4px;">Payer</span>
                    </div>
                    <div style="padding:0 16px 24px;display:flex;flex-direction:column;gap:11px;">
                        @foreach ([['Transfert · Awa D.', '-15 000'], ['Dépôt Orange Money', '+50 000'], ['Facture SENELEC', '-8 200']] as $row)
                        <div style="display:flex;align-items:center;gap:11px;">
                            <div style="width:34px;height:34px;border-radius:11px;background:var(--mist);flex-shrink:0;"></div>
                            <div style="flex:1;"><div style="height:8px;width:78%;border-radius:4px;background:#DCE4E0;margin-bottom:6px;"></div><div style="height:6px;width:50%;border-radius:4px;background:#EAEFED;"></div></div>
                            <div style="font-size:12.5px;font-weight:700;">{{ $row[1] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="wrap">
            <div class="stat-strip reveal" style="border-radius:var(--radius-md);border:1px solid var(--line);background:#fff;margin-top:56px;">
                <div><div class="num">8</div><div class="lbl">Services financiers</div></div>
                <div><div class="num">2</div><div class="lbl">Opérateurs mobile money</div></div>
                <div><div class="num">100%</div><div class="lbl">Chiffré de bout en bout</div></div>
                <div><div class="num">24/7</div><div class="lbl">Accessible depuis l'app</div></div>
            </div>
        </div>

        <div class="wrap reveal partner-strip" style="padding-top:56px;">
            <p style="text-align:center;font-size:13px;font-weight:700;color:var(--slate-light);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 24px;">Compatible avec vos opérateurs mobile money</p>
            <div style="display:flex;align-items:center;justify-content:center;gap:56px;flex-wrap:wrap;opacity:0.85;">
                <img src="{{ asset('images/partners/orange_money.svg') }}" alt="Orange Money" style="height:34px;width:auto;">
                <img src="{{ asset('images/partners/wave.svg') }}" alt="Wave" style="height:30px;width:auto;">
                <img src="{{ asset('images/partners/mixx_by_yas.svg') }}" alt="Mixx by Yas" style="height:30px;width:auto;">
            </div>
        </div>
    </section>

    <section id="fonctionnalites">
        <div class="wrap">
            <div class="section-head center reveal">
                <span class="kicker" style="justify-content:center;">Fonctionnalités</span>
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
                <span class="kicker" style="justify-content:center;">Comment ça marche</span>
                <h2>Trois étapes, et c'est fait</h2>
                <p>Pas de dossier, pas de rendez-vous en agence.</p>
            </div>
            <div class="steps">
                <div class="step reveal">
                    <div class="num">1</div>
                    <h3>Créez votre compte</h3>
                    <p>Votre numéro de téléphone, une vérification d'identité rapide, et c'est parti.</p>
                </div>
                <div class="step reveal">
                    <div class="num">2</div>
                    <h3>Approvisionnez votre solde</h3>
                    <p>Depuis Orange Money, Wave, ou tout autre moyen disponible dans l'app.</p>
                </div>
                <div class="step reveal">
                    <div class="num">3</div>
                    <h3>Utilisez votre argent</h3>
                    <p>Transférez, épargnez, payez — tout devient possible en quelques taps.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="dark">
        <div class="wrap">
            <div class="section-head center reveal">
                <span class="kicker" style="justify-content:center;">Confiance</span>
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
                <span class="kicker" style="justify-content:center;">Questions fréquentes</span>
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
                <div class="cta-row">
                    <a href="#" class="btn btn-primary">Télécharger sur l'App Store</a>
                    <a href="#" class="btn btn-ghost">Disponible sur Google Play</a>
                </div>
            </div>
        </div>
    </section>

</x-site-layout>

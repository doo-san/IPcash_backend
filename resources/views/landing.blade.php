<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPCash — La super-app financière de l'UEMOA</title>
    <meta name="description" content="Transférez, épargnez, payez et changez de devise depuis une seule application. IPCash, la néobanque pensée pour le Sénégal et l'UEMOA.">
    <link rel="icon" href="data:,">
    <style>
        :root {
            --green: #00A05B;
            --green-deep: #05613A;
            --ink: #0E1A16;
            --slate: #6B7A75;
            --mist: #F4F7F5;
            --line: #E3EAE6;
            --hero-start: #3EDCB4;
            --hero-end: #3A4CF2;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--ink);
            background: #fff;
            line-height: 1.5;
        }
        a { color: inherit; }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 0 24px; }

        header.site {
            position: sticky; top: 0; z-index: 10;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--line);
        }
        header.site .wrap {
            display: flex; align-items: center; justify-content: space-between;
            height: 72px;
        }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 20px; text-decoration: none; color: var(--ink); }
        .logo-mark {
            width: 32px; height: 32px; border-radius: 9px;
            background: linear-gradient(135deg, var(--hero-start), var(--hero-end));
            flex-shrink: 0;
        }
        nav.main { display: flex; gap: 32px; }
        nav.main a { text-decoration: none; color: var(--slate); font-weight: 500; font-size: 15px; }
        nav.main a:hover { color: var(--ink); }
        @media (max-width: 720px) { nav.main { display: none; } }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 13px 24px; border-radius: 12px; font-weight: 600; font-size: 15px;
            text-decoration: none; border: 1px solid transparent; cursor: default;
        }
        .btn-primary { background: var(--ink); color: #fff; }
        .btn-ghost { background: transparent; color: var(--ink); border-color: var(--line); }

        .hero {
            background:
                radial-gradient(circle at 15% -10%, rgba(62,220,180,0.18), transparent 45%),
                radial-gradient(circle at 85% 10%, rgba(58,76,242,0.14), transparent 45%);
            padding: 88px 0 64px;
            text-align: center;
        }
        .eyebrow {
            display: inline-block; padding: 6px 14px; border-radius: 999px;
            background: var(--mist); color: var(--green-deep); font-weight: 600; font-size: 13px;
            margin-bottom: 24px; border: 1px solid var(--line);
        }
        h1 { font-size: 48px; line-height: 1.12; margin: 0 0 20px; letter-spacing: -0.02em; }
        h1 .accent {
            background: linear-gradient(90deg, var(--hero-start), var(--hero-end));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        @media (max-width: 640px) { h1 { font-size: 34px; } }
        .lede { font-size: 19px; color: var(--slate); max-width: 560px; margin: 0 auto 36px; }
        .cta-row { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; margin-bottom: 12px; }
        .cta-note { font-size: 13px; color: var(--slate); }

        section { padding: 88px 0; }
        section.alt { background: var(--mist); }
        .section-head { text-align: center; max-width: 560px; margin: 0 auto 56px; }
        .section-head h2 { font-size: 32px; margin: 0 0 14px; letter-spacing: -0.01em; }
        .section-head p { color: var(--slate); font-size: 16px; margin: 0; }

        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        @media (max-width: 920px) { .grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) { .grid { grid-template-columns: 1fr; } }

        .card {
            background: #fff; border: 1px solid var(--line); border-radius: 18px;
            padding: 26px; text-align: left;
        }
        .card .icon {
            width: 44px; height: 44px; border-radius: 12px; background: var(--mist);
            display: flex; align-items: center; justify-content: center; margin-bottom: 18px;
            font-size: 20px;
        }
        .card h3 { font-size: 17px; margin: 0 0 8px; }
        .card p { font-size: 14px; color: var(--slate); margin: 0; }

        .security-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 32px; }
        @media (max-width: 720px) { .security-grid { grid-template-columns: 1fr; } }
        .security-item { display: flex; gap: 16px; }
        .security-item .icon {
            width: 40px; height: 40px; border-radius: 10px; background: #fff;
            border: 1px solid var(--line); flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        .security-item h3 { font-size: 16px; margin: 0 0 6px; }
        .security-item p { font-size: 14px; color: var(--slate); margin: 0; }

        .cta-band {
            background: linear-gradient(120deg, var(--ink), var(--green-deep));
            color: #fff; border-radius: 24px; padding: 56px 40px; text-align: center;
        }
        .cta-band h2 { font-size: 30px; margin: 0 0 14px; }
        .cta-band p { color: rgba(255,255,255,0.75); max-width: 480px; margin: 0 auto 32px; }
        .cta-band .btn-primary { background: #fff; color: var(--ink); }
        .cta-band .btn-ghost { border-color: rgba(255,255,255,0.35); color: #fff; }

        footer { border-top: 1px solid var(--line); padding: 40px 0; }
        footer .wrap {
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
        }
        footer .brand { display: flex; align-items: center; gap: 10px; font-weight: 600; }
        footer nav { display: flex; gap: 24px; }
        footer nav a { font-size: 14px; color: var(--slate); text-decoration: none; }
        footer .copyright { font-size: 13px; color: var(--slate); width: 100%; margin-top: 24px; }
    </style>
</head>
<body>

    <header class="site">
        <div class="wrap">
            <a href="/" class="logo">
                <span class="logo-mark"></span>
                IPCash
            </a>
            <nav class="main">
                <a href="#fonctionnalites">Fonctionnalités</a>
                <a href="#securite">Sécurité</a>
                <a href="#telecharger">Télécharger</a>
            </nav>
            <a href="#telecharger" class="btn btn-primary">Télécharger l'app</a>
        </div>
    </header>

    <section class="hero">
        <div class="wrap">
            <span class="eyebrow">Nouveau — pensé pour le Sénégal et l'UEMOA</span>
            <h1>Votre argent,<br><span class="accent">une seule application.</span></h1>
            <p class="lede">
                Transférez, épargnez, payez vos factures et changez de devise —
                tout depuis votre téléphone, en toute sécurité, sans passer par une agence.
            </p>
            <div class="cta-row">
                <a href="#telecharger" class="btn btn-primary">Télécharger sur l'App Store</a>
                <a href="#telecharger" class="btn btn-ghost">Disponible sur Google Play</a>
            </div>
            <p class="cta-note">Bientôt disponible</p>
        </div>
    </section>

    <section id="fonctionnalites">
        <div class="wrap">
            <div class="section-head">
                <h2>Tout ce dont vous avez besoin, au quotidien</h2>
                <p>Une seule app pour gérer votre argent, sans jongler entre plusieurs opérateurs.</p>
            </div>
            <div class="grid">
                <div class="card">
                    <div class="icon">💸</div>
                    <h3>Transfert instantané</h3>
                    <p>Envoyez de l'argent à un proche en quelques secondes, sans frais cachés.</p>
                </div>
                <div class="card">
                    <div class="icon">📲</div>
                    <h3>Mobile money</h3>
                    <p>Déposez et retirez directement depuis Orange Money ou Wave.</p>
                </div>
                <div class="card">
                    <div class="icon">💱</div>
                    <h3>IPchange</h3>
                    <p>Détenez et échangez plusieurs devises au meilleur taux, sans quitter l'app.</p>
                </div>
                <div class="card">
                    <div class="icon">💳</div>
                    <h3>Carte virtuelle</h3>
                    <p>Générez une carte prépayée pour payer en ligne en toute sécurité.</p>
                </div>
                <div class="card">
                    <div class="icon">🏺</div>
                    <h3>Poches d'épargne</h3>
                    <p>Mettez de côté pour vos projets avec des objectifs personnalisés.</p>
                </div>
                <div class="card">
                    <div class="icon">🧾</div>
                    <h3>Factures &amp; QR</h3>
                    <p>Payez eau, électricité, Canal+ et vos achats en scannant un code QR.</p>
                </div>
                <div class="card">
                    <div class="icon">📶</div>
                    <h3>Crédit &amp; eSIM</h3>
                    <p>Rechargez votre forfait ou activez un eSIM voyage en quelques taps.</p>
                </div>
                <div class="card">
                    <div class="icon">🛡️</div>
                    <h3>Assurance</h3>
                    <p>Souscrivez une assurance auto directement depuis votre compte.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="securite" class="alt">
        <div class="wrap">
            <div class="section-head">
                <h2>Votre sécurité, notre priorité</h2>
                <p>Votre argent et vos données sont protégés à chaque étape.</p>
            </div>
            <div class="security-grid">
                <div class="security-item">
                    <div class="icon">🔒</div>
                    <div>
                        <h3>Code secret &amp; biométrie</h3>
                        <p>Chaque opération sensible est confirmée par votre code PIN ou Face ID/empreinte.</p>
                    </div>
                </div>
                <div class="security-item">
                    <div class="icon">🪪</div>
                    <div>
                        <h3>Identité vérifiée</h3>
                        <p>Un processus de vérification d'identité complet avant tout accès à votre compte.</p>
                    </div>
                </div>
                <div class="security-item">
                    <div class="icon">📡</div>
                    <div>
                        <h3>Sessions sous contrôle</h3>
                        <p>Consultez les appareils connectés à votre compte et révoquez-les à tout moment.</p>
                    </div>
                </div>
                <div class="security-item">
                    <div class="icon">🚫</div>
                    <div>
                        <h3>Blocage à distance</h3>
                        <p>Bloquez votre compte ou votre carte immédiatement en cas de perte ou de vol.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="telecharger">
        <div class="wrap">
            <div class="cta-band">
                <h2>Prêt à simplifier votre argent ?</h2>
                <p>IPCash arrive bientôt sur l'App Store et Google Play.</p>
                <div class="cta-row">
                    <a href="#" class="btn btn-primary">Télécharger sur l'App Store</a>
                    <a href="#" class="btn btn-ghost">Disponible sur Google Play</a>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="wrap">
            <div class="brand">
                <span class="logo-mark" style="width:24px;height:24px;border-radius:7px;"></span>
                IPCash
            </div>
            <nav>
                <a href="#fonctionnalites">Fonctionnalités</a>
                <a href="#securite">Sécurité</a>
                <a href="/admin">Espace administrateur</a>
            </nav>
            <p class="copyright">© {{ date('Y') }} IPCash. Tous droits réservés.</p>
        </div>
    </footer>

</body>
</html>

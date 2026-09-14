<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPCash — La super-app financière de l'UEMOA</title>
    <meta name="description" content="Transférez, épargnez, payez et changez de devise depuis une seule application. IPCash, la néobanque pensée pour le Sénégal et l'UEMOA.">
    <link rel="icon" href="data:,">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            --radius-lg: 24px;
            --radius-md: 16px;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--ink);
            background: #fff;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }
        img { max-width: 100%; display: block; }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 0 24px; }

        /* ---------- Header ---------- */
        header.site {
            position: sticky; top: 0; z-index: 20;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--line);
        }
        header.site .wrap {
            display: flex; align-items: center; justify-content: space-between;
            height: 76px;
        }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 20px; text-decoration: none; color: var(--ink); letter-spacing: -0.01em; }
        .logo-mark {
            width: 34px; height: 34px; border-radius: 10px;
            background: linear-gradient(135deg, var(--hero-start), var(--hero-end));
            flex-shrink: 0;
        }
        nav.main { display: flex; gap: 36px; }
        nav.main a { text-decoration: none; color: var(--slate); font-weight: 600; font-size: 15px; transition: color .15s; }
        nav.main a:hover { color: var(--ink); }
        .header-actions { display: flex; align-items: center; gap: 20px; }
        .header-actions .admin-link { font-size: 14px; font-weight: 600; color: var(--slate); text-decoration: none; }
        @media (max-width: 780px) { nav.main, .admin-link { display: none; } }

        /* ---------- Buttons ---------- */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            padding: 14px 26px; border-radius: 14px; font-weight: 700; font-size: 15px;
            text-decoration: none; border: 1.5px solid transparent; white-space: nowrap;
            transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
        }
        .btn-primary { background: var(--ink); color: #fff; box-shadow: 0 8px 20px -8px rgba(14,26,22,0.45); }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 12px 24px -8px rgba(14,26,22,0.5); }
        .btn-ghost { background: #fff; color: var(--ink); border-color: var(--line); }
        .btn-ghost:hover { border-color: var(--ink); }
        .btn-sm { padding: 10px 18px; font-size: 14px; border-radius: 11px; }

        /* ---------- Hero ---------- */
        .hero { position: relative; overflow: hidden; padding: 100px 0 40px; }
        .hero::before {
            content: ""; position: absolute; inset: -20% -10% auto -10%; height: 640px;
            background:
                radial-gradient(circle at 20% 20%, rgba(62,220,180,0.22), transparent 55%),
                radial-gradient(circle at 80% 0%, rgba(58,76,242,0.16), transparent 50%);
            z-index: -1; pointer-events: none;
        }
        .hero-inner { display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 40px; align-items: center; }
        @media (max-width: 920px) { .hero-inner { grid-template-columns: 1fr; } }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px 7px 8px; border-radius: 999px;
            background: #fff; color: var(--green-deep); font-weight: 700; font-size: 13px;
            margin-bottom: 26px; border: 1px solid var(--line); box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .eyebrow .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green); box-shadow: 0 0 0 3px rgba(0,160,91,0.18); }
        h1 { font-size: 56px; font-weight: 800; line-height: 1.08; margin: 0 0 22px; letter-spacing: -0.025em; }
        h1 .accent {
            background: linear-gradient(90deg, var(--hero-start), var(--hero-end));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        @media (max-width: 640px) { h1 { font-size: 36px; } }
        .lede { font-size: 19px; color: var(--slate); max-width: 480px; margin: 0 0 36px; }
        .cta-row { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; }
        .cta-note { font-size: 13px; color: var(--slate); display: flex; align-items: center; gap: 8px; }
        .cta-note svg { flex-shrink: 0; }

        /* ---------- Phone mockup (no real screenshots yet — built in CSS) ---------- */
        .phone-stage { display: flex; justify-content: center; position: relative; }
        .phone {
            width: 280px; border-radius: 42px; background: var(--ink); padding: 12px;
            box-shadow: 0 40px 80px -30px rgba(14,26,22,0.45), 0 10px 24px -12px rgba(14,26,22,0.25);
            transform: rotate(2deg);
        }
        .phone-screen { background: #fff; border-radius: 32px; overflow: hidden; }
        .phone-notch { height: 26px; display: flex; align-items: flex-end; justify-content: center; padding-bottom: 4px; }
        .phone-notch span { width: 70px; height: 5px; border-radius: 999px; background: rgba(0,0,0,0.15); }
        .phone-balance {
            margin: 6px 14px 14px; padding: 20px; border-radius: 20px; color: #fff;
            background: linear-gradient(135deg, var(--hero-start), var(--hero-end));
        }
        .phone-balance small { opacity: .8; font-size: 12px; font-weight: 600; }
        .phone-balance .amount { font-size: 26px; font-weight: 800; margin-top: 6px; letter-spacing: -0.02em; }
        .phone-actions { display: flex; gap: 10px; padding: 0 14px 16px; }
        .phone-actions span {
            flex: 1; text-align: center; font-size: 11px; font-weight: 700; color: var(--ink);
            background: var(--mist); border-radius: 12px; padding: 10px 4px;
        }
        .phone-list { padding: 0 14px 20px; display: flex; flex-direction: column; gap: 10px; }
        .phone-row { display: flex; align-items: center; gap: 10px; }
        .phone-row .dot { width: 32px; height: 32px; border-radius: 10px; background: var(--mist); flex-shrink: 0; }
        .phone-row .lines { flex: 1; }
        .phone-row .l1 { height: 8px; width: 70%; border-radius: 4px; background: #DCE4E0; margin-bottom: 6px; }
        .phone-row .l2 { height: 6px; width: 45%; border-radius: 4px; background: #EAEFED; }
        .phone-row .amt { font-size: 12px; font-weight: 700; }
        .float-card {
            position: absolute; background: #fff; border-radius: 16px; padding: 12px 16px;
            box-shadow: 0 16px 32px -12px rgba(14,26,22,0.22); border: 1px solid var(--line);
            display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 700;
        }
        .float-card.c1 { top: 8%; left: -6%; }
        .float-card.c2 { bottom: 10%; right: -8%; }
        @media (max-width: 920px) { .float-card { display: none; } }

        /* ---------- Sections ---------- */
        section { padding: 96px 0; }
        section.tight { padding: 72px 0; }
        section.alt { background: var(--mist); }
        .section-head { max-width: 620px; margin: 0 0 52px; }
        .section-head.center { text-align: center; margin-left: auto; margin-right: auto; }
        .kicker { display: block; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--green-deep); margin-bottom: 12px; }
        .section-head h2 { font-size: 34px; font-weight: 800; margin: 0 0 14px; letter-spacing: -0.02em; }
        .section-head p { color: var(--slate); font-size: 16px; margin: 0; }

        /* ---------- Bento feature grid ---------- */
        .bento { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: 190px; gap: 18px; }
        .bento .card.span-2 { grid-column: span 2; }
        .bento .card.row-2 { grid-row: span 2; }
        @media (max-width: 980px) {
            .bento { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 180px; }
            .bento .card.span-2 { grid-column: span 2; }
            .bento .card.row-2 { grid-row: span 1; }
        }
        @media (max-width: 560px) {
            .bento { grid-template-columns: 1fr; grid-auto-rows: auto; }
            .bento .card.span-2 { grid-column: span 1; }
        }
        .card {
            background: #fff; border: 1px solid var(--line); border-radius: var(--radius-md);
            padding: 24px; text-align: left; display: flex; flex-direction: column;
            transition: border-color .15s, transform .15s;
        }
        .card:hover { border-color: #C9D6D0; }
        .card .icon {
            width: 40px; height: 40px; border-radius: 11px; background: var(--mist);
            display: flex; align-items: center; justify-content: center; margin-bottom: auto;
            font-size: 19px; flex-shrink: 0;
        }
        .card h3 { font-size: 16px; font-weight: 700; margin: 14px 0 6px; }
        .card p { font-size: 13.5px; color: var(--slate); margin: 0; }

        /* ---------- Alternating deep-dive rows ---------- */
        .deep-dive { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center; margin-bottom: 96px; }
        .deep-dive:last-child { margin-bottom: 0; }
        .deep-dive.reverse .deep-visual { order: 2; }
        @media (max-width: 860px) {
            .deep-dive, .deep-dive.reverse { grid-template-columns: 1fr; gap: 32px; }
            .deep-dive.reverse .deep-visual { order: 0; }
        }
        .deep-dive .kicker { margin-bottom: 10px; }
        .deep-dive h3 { font-size: 27px; font-weight: 800; margin: 0 0 14px; letter-spacing: -0.015em; }
        .deep-dive p { color: var(--slate); font-size: 16px; margin: 0 0 22px; }
        .check-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 12px; }
        .check-list li { display: flex; gap: 10px; font-size: 14.5px; font-weight: 600; }
        .check-list svg { flex-shrink: 0; margin-top: 2px; }
        .deep-visual {
            border-radius: var(--radius-lg); background: var(--mist); border: 1px solid var(--line);
            aspect-ratio: 4/3; display: flex; align-items: center; justify-content: center;
            overflow: hidden; position: relative;
        }
        .deep-visual .big-icon { font-size: 64px; }
        .deep-visual.grad-1 { background: linear-gradient(135deg, #E9FBF4, #EAF0FF); }
        .deep-visual.grad-2 { background: linear-gradient(135deg, #FFF6E9, #FCEAF6); }

        /* ---------- Security ---------- */
        .security-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 28px; }
        @media (max-width: 720px) { .security-grid { grid-template-columns: 1fr; } }
        .security-item { display: flex; gap: 16px; background: #fff; border: 1px solid var(--line); border-radius: var(--radius-md); padding: 22px; }
        .security-item .icon {
            width: 42px; height: 42px; border-radius: 11px; background: var(--mist); flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 19px;
        }
        .security-item h3 { font-size: 16px; font-weight: 700; margin: 0 0 6px; }
        .security-item p { font-size: 14px; color: var(--slate); margin: 0; }

        /* ---------- FAQ ---------- */
        .faq { max-width: 760px; margin: 0 auto; }
        .faq-item { border-bottom: 1px solid var(--line); }
        .faq-item summary {
            list-style: none; cursor: pointer; padding: 22px 4px; display: flex; align-items: center;
            justify-content: space-between; font-weight: 700; font-size: 16px; gap: 16px;
        }
        .faq-item summary::-webkit-details-marker { display: none; }
        .faq-item summary .plus { flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%; background: var(--mist); position: relative; }
        .faq-item summary .plus::before, .faq-item summary .plus::after {
            content: ""; position: absolute; background: var(--ink); border-radius: 2px;
        }
        .faq-item summary .plus::before { top: 50%; left: 5px; right: 5px; height: 2px; transform: translateY(-50%); }
        .faq-item summary .plus::after { left: 50%; top: 5px; bottom: 5px; width: 2px; transform: translateX(-50%); transition: opacity .15s; }
        .faq-item[open] summary .plus::after { opacity: 0; }
        .faq-item p { margin: 0 0 22px; padding-right: 34px; color: var(--slate); font-size: 15px; }

        /* ---------- CTA band ---------- */
        .cta-band {
            background: linear-gradient(120deg, var(--ink), var(--green-deep));
            color: #fff; border-radius: var(--radius-lg); padding: 64px 40px; text-align: center;
            position: relative; overflow: hidden;
        }
        .cta-band::after {
            content: ""; position: absolute; inset: 0;
            background: radial-gradient(circle at 80% 20%, rgba(62,220,180,0.25), transparent 55%);
        }
        .cta-band > * { position: relative; }
        .cta-band h2 { font-size: 32px; font-weight: 800; margin: 0 0 14px; letter-spacing: -0.02em; }
        .cta-band p { color: rgba(255,255,255,0.75); max-width: 480px; margin: 0 auto 32px; font-size: 16px; }
        .cta-band .cta-row { justify-content: center; }
        .cta-band .btn-primary { background: #fff; color: var(--ink); box-shadow: none; }
        .cta-band .btn-ghost { border-color: rgba(255,255,255,0.3); color: #fff; background: transparent; }

        /* ---------- Footer ---------- */
        footer { border-top: 1px solid var(--line); padding: 64px 0 32px; }
        .footer-grid { display: grid; grid-template-columns: 1.4fr repeat(4, 1fr); gap: 32px; margin-bottom: 48px; }
        @media (max-width: 860px) { .footer-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 520px) { .footer-grid { grid-template-columns: 1fr; } }
        .footer-brand .logo { margin-bottom: 14px; }
        .footer-brand p { color: var(--slate); font-size: 14px; max-width: 260px; margin: 0; }
        .footer-col h4 { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; margin: 0 0 16px; color: var(--slate); }
        .footer-col ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 12px; }
        .footer-col a { font-size: 14.5px; text-decoration: none; color: var(--ink); font-weight: 500; }
        .footer-col a:hover { color: var(--green-deep); }
        .footer-bottom {
            border-top: 1px solid var(--line); padding-top: 28px; display: flex; align-items: center;
            justify-content: space-between; flex-wrap: wrap; gap: 12px;
        }
        .footer-bottom p { margin: 0; font-size: 13px; color: var(--slate); }
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
                <a href="#services">Services</a>
                <a href="#securite">Sécurité</a>
                <a href="#faq">FAQ</a>
            </nav>
            <div class="header-actions">
                <a href="/admin" class="admin-link">Espace administrateur</a>
                <a href="#telecharger" class="btn btn-primary btn-sm">Télécharger</a>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="wrap">
            <div class="hero-inner">
                <div>
                    <span class="eyebrow"><span class="dot"></span> Pensé pour le Sénégal et l'UEMOA</span>
                    <h1>Votre argent,<br><span class="accent">enfin simple.</span></h1>
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
                <div class="phone-stage">
                    <div class="float-card c1">🔒&nbsp; Compte vérifié</div>
                    <div class="float-card c2">⚡&nbsp; Transfert instantané</div>
                    <div class="phone">
                        <div class="phone-screen">
                            <div class="phone-notch"><span></span></div>
                            <div class="phone-balance">
                                <small>SOLDE DISPONIBLE</small>
                                <div class="amount">248 500 F</div>
                            </div>
                            <div class="phone-actions">
                                <span>Envoyer</span>
                                <span>Déposer</span>
                                <span>Payer</span>
                            </div>
                            <div class="phone-list">
                                <div class="phone-row">
                                    <div class="dot"></div>
                                    <div class="lines"><div class="l1"></div><div class="l2"></div></div>
                                    <div class="amt">-15 000</div>
                                </div>
                                <div class="phone-row">
                                    <div class="dot"></div>
                                    <div class="lines"><div class="l1"></div><div class="l2"></div></div>
                                    <div class="amt">+50 000</div>
                                </div>
                                <div class="phone-row">
                                    <div class="dot"></div>
                                    <div class="lines"><div class="l1"></div><div class="l2"></div></div>
                                    <div class="amt">-8 200</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="fonctionnalites">
        <div class="wrap">
            <div class="section-head center">
                <span class="kicker">Fonctionnalités</span>
                <h2>Tout ce dont vous avez besoin, au quotidien</h2>
                <p>Une seule app pour gérer votre argent, sans jongler entre plusieurs opérateurs.</p>
            </div>
            <div class="bento">
                <div class="card span-2 row-2">
                    <div class="icon">💸</div>
                    <h3>Transfert instantané</h3>
                    <p>Envoyez de l'argent à un proche en quelques secondes, sans frais cachés — vers un contact IPCash ou par numéro de téléphone.</p>
                </div>
                <div class="card">
                    <div class="icon">📲</div>
                    <h3>Mobile money</h3>
                    <p>Déposez et retirez directement depuis Orange Money ou Wave.</p>
                </div>
                <div class="card">
                    <div class="icon">💱</div>
                    <h3>IPchange</h3>
                    <p>Détenez et échangez plusieurs devises au meilleur taux.</p>
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
                <div class="card span-2">
                    <div class="icon">🧾</div>
                    <h3>Factures &amp; QR</h3>
                    <p>Payez eau, électricité, Canal+ et vos achats du quotidien en scannant un simple code QR.</p>
                </div>
                <div class="card">
                    <div class="icon">📶</div>
                    <h3>Crédit &amp; eSIM</h3>
                    <p>Rechargez votre forfait ou activez un eSIM voyage.</p>
                </div>
                <div class="card">
                    <div class="icon">🛡️</div>
                    <h3>Assurance</h3>
                    <p>Souscrivez une assurance auto depuis votre compte.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="services" class="alt">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker">En détail</span>
                <h2>Conçu pour votre vie financière réelle</h2>
            </div>

            <div class="deep-dive">
                <div>
                    <span class="kicker">IPchange</span>
                    <h3>Vos devises, sous contrôle</h3>
                    <p>Ouvrez des sous-comptes en devises étrangères, convertissez à tout moment au meilleur taux et envoyez directement en devise à l'international — sans intermédiaire.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Taux transparent, affiché avant conversion</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Plusieurs devises détenues simultanément</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#E9FBF4"/><path d="M8 12l2.5 2.5L16 9" stroke="#05613A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Conversion instantanée, 24h/24</li>
                    </ul>
                </div>
                <div class="deep-visual grad-1"><span class="big-icon">💱</span></div>
            </div>

            <div class="deep-dive reverse">
                <div>
                    <span class="kicker">Carte virtuelle</span>
                    <h3>Payez en ligne, sans exposer votre carte réelle</h3>
                    <p>Générez une carte prépayée en un instant pour vos achats en ligne. Rechargez-la depuis votre solde principal et gardez le contrôle total sur son plafond.</p>
                    <ul class="check-list">
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#FFF6E9"/><path d="M8 12l2.5 2.5L16 9" stroke="#8A5A00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Génération instantanée</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#FFF6E9"/><path d="M8 12l2.5 2.5L16 9" stroke="#8A5A00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Gel ou blocage à tout moment</li>
                        <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#FFF6E9"/><path d="M8 12l2.5 2.5L16 9" stroke="#8A5A00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Aucune donnée bancaire réelle exposée</li>
                    </ul>
                </div>
                <div class="deep-visual grad-2"><span class="big-icon">💳</span></div>
            </div>
        </div>
    </section>

    <section id="securite">
        <div class="wrap">
            <div class="section-head center">
                <span class="kicker">Confiance</span>
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

    <section id="faq" class="alt">
        <div class="wrap">
            <div class="section-head center">
                <span class="kicker">Questions fréquentes</span>
                <h2>Tout ce qu'il faut savoir</h2>
            </div>
            <div class="faq">
                <details class="faq-item" open>
                    <summary>IPCash est-il gratuit ?</summary>
                    <p>L'ouverture de compte et l'application sont gratuites. Certaines opérations (dépôt/retrait mobile money, conversion de devise) peuvent comporter des frais, toujours affichés avant confirmation.</p>
                </details>
                <details class="faq-item">
                    <summary>Comment vérifier mon identité ?</summary>
                    <p>À l'inscription, vous photographiez une pièce d'identité et prenez un selfie. La vérification est nécessaire avant tout mouvement d'argent, pour la sécurité de votre compte.</p>
                </details>
                <details class="faq-item">
                    <summary>Quels opérateurs mobile money sont supportés ?</summary>
                    <p>Orange Money et Wave sont pris en charge pour les dépôts et retraits, directement depuis l'application.</p>
                </details>
                <details class="faq-item">
                    <summary>Que faire si je perds mon téléphone ?</summary>
                    <p>Bloquez votre compte immédiatement depuis un autre appareil ou contactez le support — aucune opération ne sera possible tant que le blocage est actif.</p>
                </details>
            </div>
        </div>
    </section>

    <section id="telecharger" class="tight">
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
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="/" class="logo"><span class="logo-mark"></span>IPCash</a>
                    <p>La super-app financière pensée pour le Sénégal et l'UEMOA.</p>
                </div>
                <div class="footer-col">
                    <h4>Produit</h4>
                    <ul>
                        <li><a href="#fonctionnalites">Fonctionnalités</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#securite">Sécurité</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Ressources</h4>
                    <ul>
                        <li><a href="#faq">FAQ</a></li>
                        <li><a href="/admin">Espace administrateur</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Télécharger</h4>
                    <ul>
                        <li><a href="#telecharger">App Store</a></li>
                        <li><a href="#telecharger">Google Play</a></li>
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

</body>
</html>

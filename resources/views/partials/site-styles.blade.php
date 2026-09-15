<style>
    :root {
        --green: #00A05B;
        --green-deep: #05613A;
        --ink: #0E1A16;
        --slate: #6B7A75;
        --slate-light: #97A39E;
        --mist: #F4F7F5;
        --mist-deep: #ECF1EE;
        --line: #E3EAE6;
        --hero-start: #3EDCB4;
        --hero-mid: #4A8FE8;
        --hero-end: #3A4CF2;
        --sun: #FFB648;
        --pink: #F26FA0;
        --radius-xl: 32px;
        --radius-lg: 24px;
        --radius-md: 16px;
        --radius-sm: 11px;
        --serif: 'Fraunces', Georgia, serif;
        --sans: 'Manrope', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
        margin: 0;
        font-family: var(--sans);
        color: var(--ink);
        background: #fff;
        line-height: 1.55;
        -webkit-font-smoothing: antialiased;
        overflow-x: hidden;
    }
    a { color: inherit; }
    img, svg { max-width: 100%; display: block; }
    .wrap { max-width: 1180px; margin: 0 auto; padding: 0 24px; }
    .wrap-narrow { max-width: 820px; margin: 0 auto; padding: 0 24px; }

    /* ---------- Reveal-on-scroll ---------- */
    .reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s cubic-bezier(.16,.8,.3,1), transform .7s cubic-bezier(.16,.8,.3,1); }
    .reveal.in { opacity: 1; transform: translateY(0); }
    .reveal-scale { opacity: 0; transform: scale(.94); transition: opacity .7s ease, transform .7s cubic-bezier(.16,.8,.3,1); }
    .reveal-scale.in { opacity: 1; transform: scale(1); }
    @media (prefers-reduced-motion: reduce) {
        .reveal, .reveal-scale { opacity: 1; transform: none; transition: none; }
    }

    /* ---------- Header ---------- */
    header.site {
        position: sticky; top: 0; z-index: 30;
        background: #fff;
        border-bottom: 1px solid var(--line);
    }
    header.site .wrap { display: flex; align-items: center; justify-content: space-between; height: 76px; }
    .logo { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 20px; text-decoration: none; color: var(--ink); letter-spacing: -0.01em; }
    .logo-mark { width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0; object-fit: contain; }
    nav.main { display: flex; gap: 32px; }
    nav.main a { text-decoration: none; color: var(--slate); font-weight: 600; font-size: 15px; transition: color .15s; position: relative; }
    nav.main a:hover, nav.main a.active { color: var(--ink); }
    nav.main a.active::after { content: ""; position: absolute; left: 0; right: 0; bottom: -27px; height: 2px; background: var(--ink); }
    .header-actions { display: flex; align-items: center; gap: 20px; }
    .header-actions .login-link { font-size: 14px; font-weight: 600; color: var(--slate); text-decoration: none; }
    .nav-toggle { display: none; background: none; border: none; padding: 8px; cursor: pointer; }
    .nav-toggle span { display: block; width: 22px; height: 2px; background: var(--ink); margin: 5px 0; border-radius: 2px; }
    @media (max-width: 860px) {
        nav.main, .login-link { display: none; }
        .nav-toggle { display: block; }
        .mobile-nav { display: flex; }
    }
    .mobile-nav {
        display: none; flex-direction: column; gap: 2px; background: #fff; border-bottom: 1px solid var(--line);
        padding: 8px 24px 20px;
    }
    .mobile-nav.open { display: flex; }
    .mobile-nav a { padding: 12px 0; font-weight: 600; text-decoration: none; color: var(--ink); border-bottom: 1px solid var(--mist); font-size: 15px; }

    /* ---------- Buttons ---------- */
    .btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 9px;
        padding: 15px 28px; border-radius: 14px; font-weight: 700; font-size: 15px;
        text-decoration: none; border: 1.5px solid transparent; white-space: nowrap;
        transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease, background .15s;
    }
    .btn-primary { background: var(--ink); color: #fff; box-shadow: 0 8px 20px -8px rgba(14,26,22,0.45); }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 14px 26px -8px rgba(14,26,22,0.5); }
    .btn-ghost { background: #fff; color: var(--ink); border-color: var(--line); }
    .btn-ghost:hover { border-color: var(--ink); transform: translateY(-2px); }
    .btn-sm { padding: 10px 18px; font-size: 14px; border-radius: 11px; }

    /* ---------- Kicker / headings ---------- */
    .kicker { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--green-deep); margin-bottom: 14px; }
    .kicker::before { content: ""; width: 18px; height: 2px; background: var(--green); border-radius: 2px; }
    h1, h2, h3 { font-family: var(--serif); font-weight: 600; letter-spacing: -0.01em; }
    .section-head { max-width: 640px; margin: 0 0 40px; }
    .section-head.center { text-align: center; margin-left: auto; margin-right: auto; }
    .section-head h2 { font-size: 38px; margin: 0 0 16px; }
    .section-head p { color: var(--slate); font-size: 17px; margin: 0; font-family: var(--sans); }

    section { padding: 64px 0; }
    section.tight { padding: 48px 0; }
    section.alt { background: var(--mist); }

    /* ---------- Page hero (inner pages) ---------- */
    .page-hero { padding: 72px 0 64px; position: relative; overflow: hidden; }
    .page-hero::before {
        content: ""; position: absolute; inset: -30% -10% auto -10%; height: 520px;
        background: radial-gradient(circle at 20% 20%, rgba(62,220,180,0.18), transparent 55%), radial-gradient(circle at 80% 0%, rgba(58,76,242,0.13), transparent 50%);
        z-index: -1;
    }
    .page-hero h1 { font-size: 48px; margin: 0 0 20px; max-width: 720px; }
    .page-hero p.lede { font-size: 19px; color: var(--slate); max-width: 560px; font-family: var(--sans); margin: 0; }
    @media (max-width: 640px) { .page-hero h1 { font-size: 32px; } }

    /* ---------- Stat strip ---------- */
    .stat-strip { display: grid; grid-template-columns: repeat(4, 1fr); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
    @media (max-width: 720px) { .stat-strip { grid-template-columns: repeat(2, 1fr); } }
    .stat-strip div { padding: 32px 24px; text-align: center; border-left: 1px solid var(--line); }
    .stat-strip div:first-child { border-left: none; }
    @media (max-width: 720px) { .stat-strip div:nth-child(2n+1) { border-left: none; } }
    .stat-strip .num { font-family: var(--serif); font-size: 32px; font-weight: 600; color: var(--ink); }
    .stat-strip .lbl { font-size: 13px; color: var(--slate); font-weight: 600; margin-top: 4px; }

    /* ---------- Bento feature grid ---------- */
    .bento { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: 200px; gap: 18px; }
    .bento .card.span-2 { grid-column: span 2; }
    .bento .card.row-2 { grid-row: span 2; }
    @media (max-width: 980px) {
        .bento { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 190px; }
        .bento .card.row-2 { grid-row: span 1; }
    }
    @media (max-width: 560px) {
        .bento { grid-template-columns: 1fr; grid-auto-rows: auto; }
        .bento .card.span-2 { grid-column: span 1; }
    }
    .card {
        background: #fff; border: 1px solid var(--line); border-radius: var(--radius-md);
        padding: 26px; text-align: left; display: flex; flex-direction: column;
        transition: border-color .2s, transform .2s, box-shadow .2s;
    }
    .card:hover { border-color: #C9D6D0; transform: translateY(-3px); box-shadow: 0 16px 32px -18px rgba(14,26,22,0.25); }
    .card .icon { width: 46px; height: 46px; border-radius: 50%; background: var(--mist); display: flex; align-items: center; justify-content: center; margin-bottom: auto; font-size: 20px; flex-shrink: 0; box-shadow: 0 6px 14px -6px rgba(14,26,22,0.3); }
    .card h3 { font-family: var(--sans); font-size: 16.5px; font-weight: 700; margin: 16px 0 6px; }
    .card p { font-size: 13.5px; color: var(--slate); margin: 0; }

    /* ---------- Alternating deep-dive rows ---------- */
    .deep-dive { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center; scroll-margin-top: 96px; }
    .deep-dive + .deep-dive { margin-top: 68px; }
    .deep-dive.reverse .deep-visual { order: 2; }
    @media (max-width: 860px) {
        .deep-dive, .deep-dive.reverse { grid-template-columns: 1fr; gap: 32px; }
        .deep-dive.reverse .deep-visual { order: 0; }
    }
    .deep-dive h3 { font-size: 28px; margin: 0 0 14px; }
    .feature-tag {
        display: inline-block; font-family: var(--sans); font-size: 15px; font-weight: 800;
        padding: 8px 18px; border-radius: 999px; margin-bottom: 18px; letter-spacing: -0.01em;
    }
    .deep-dive p.desc { color: var(--slate); font-size: 16px; margin: 0 0 24px; font-family: var(--sans); }
    .check-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 13px; }
    .check-list li { display: flex; gap: 10px; font-size: 14.5px; font-weight: 600; }
    .check-list svg { flex-shrink: 0; margin-top: 2px; }
    .deep-visual {
        border-radius: var(--radius-lg); background: var(--mist); border: 1px solid var(--line);
        aspect-ratio: 4/3; display: flex; align-items: center; justify-content: center;
        overflow: hidden; position: relative;
    }
    .deep-visual .big-icon { font-size: 68px; }
    .big-icon-badge {
        width: 108px; height: 108px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 20px 40px -14px rgba(14,26,22,0.35);
    }
    .deep-visual.grad-1 { background: linear-gradient(135deg, #E9FBF4, #EAF0FF); }
    .deep-visual.grad-2 { background: linear-gradient(135deg, #FFF6E9, #FCEAF6); }
    .deep-visual.grad-3 { background: linear-gradient(135deg, #EAF0FF, #F5E9FF); }
    .deep-visual.grad-4 { background: linear-gradient(135deg, #FCEAF6, #E9FBF4); }

    /* ---------- Feature quick nav ---------- */
    .feature-nav {
        display: flex; gap: 10px; overflow-x: auto; padding: 4px 0 28px; margin-bottom: 8px;
        border-bottom: 1px solid var(--line); scrollbar-width: none;
    }
    .feature-nav::-webkit-scrollbar { display: none; }
    .feature-nav a {
        display: inline-flex; align-items: center; gap: 9px; flex-shrink: 0; padding: 9px 16px 9px 9px;
        border-radius: 999px; border: 1px solid var(--line); background: #fff; text-decoration: none;
        color: var(--ink); font-size: 13.5px; font-weight: 600; transition: border-color .15s, transform .15s;
    }
    .feature-nav a:hover { border-color: var(--ink); transform: translateY(-1px); }
    .feature-nav-icon { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

    /* ---------- Compact secondary feature grid ---------- */
    .compact-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    @media (max-width: 860px) { .compact-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px) { .compact-grid { grid-template-columns: 1fr; } }
    .compact-card {
        display: flex; gap: 16px; align-items: flex-start; background: #fff; border: 1px solid var(--line);
        border-radius: var(--radius-md); padding: 22px; scroll-margin-top: 96px;
        transition: transform .2s, box-shadow .2s;
    }
    .compact-card:hover { transform: translateY(-3px); box-shadow: 0 16px 32px -18px rgba(14,26,22,0.25); }
    .compact-card .icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .compact-card h3 { font-family: var(--sans); font-size: 16px; margin: 0 0 6px; }
    .compact-card p { font-size: 13.5px; color: var(--slate); margin: 0; }

    /* ---------- Security ---------- */
    .security-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 28px; }
    @media (max-width: 720px) { .security-grid { grid-template-columns: 1fr; } }
    .security-item { display: flex; gap: 16px; background: #fff; border: 1px solid var(--line); border-radius: var(--radius-md); padding: 24px; transition: transform .2s, box-shadow .2s; }
    .security-item:hover { transform: translateY(-3px); box-shadow: 0 16px 32px -18px rgba(14,26,22,0.2); }
    .security-item .icon { width: 44px; height: 44px; border-radius: 12px; background: var(--mist); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .security-item h3 { font-family: var(--sans); font-size: 16.5px; font-weight: 700; margin: 0 0 6px; }
    .security-item p { font-size: 14px; color: var(--slate); margin: 0; }

    /* ---------- FAQ ---------- */
    .faq-item { border-bottom: 1px solid var(--line); }
    .faq-item summary { list-style: none; cursor: pointer; padding: 22px 4px; display: flex; align-items: center; justify-content: space-between; font-weight: 700; font-size: 16px; gap: 16px; font-family: var(--sans); }
    .faq-item summary::-webkit-details-marker { display: none; }
    .faq-item summary .plus { flex-shrink: 0; width: 24px; height: 24px; border-radius: 50%; background: var(--mist); position: relative; }
    .faq-item summary .plus::before, .faq-item summary .plus::after { content: ""; position: absolute; background: var(--ink); border-radius: 2px; }
    .faq-item summary .plus::before { top: 50%; left: 6px; right: 6px; height: 2px; transform: translateY(-50%); }
    .faq-item summary .plus::after { left: 50%; top: 6px; bottom: 6px; width: 2px; transform: translateX(-50%); transition: opacity .15s; }
    .faq-item[open] summary .plus::after { opacity: 0; }
    .faq-item p { margin: 0 0 22px; padding-right: 34px; color: var(--slate); font-size: 15px; }

    /* ---------- CTA band ---------- */
    .cta-band { background: linear-gradient(120deg, var(--ink), var(--green-deep)); color: #fff; border-radius: var(--radius-lg); padding: 64px 40px; text-align: center; position: relative; overflow: hidden; }
    .cta-band::after { content: ""; position: absolute; inset: 0; background: radial-gradient(circle at 80% 20%, rgba(62,220,180,0.25), transparent 55%); }
    .cta-band > * { position: relative; }
    .cta-band h2 { font-size: 34px; margin: 0 0 14px; color: #fff; }
    .cta-band p { color: rgba(255,255,255,0.75); max-width: 480px; margin: 0 auto 32px; font-size: 16px; font-family: var(--sans); }
    .cta-row { display: flex; gap: 14px; flex-wrap: wrap; }
    .cta-band .cta-row { justify-content: center; }
    .cta-band .btn-primary { background: #fff; color: var(--ink); box-shadow: none; }
    .cta-band .btn-ghost { border-color: rgba(255,255,255,0.3); color: #fff; background: transparent; }

    /* ---------- Store badges ---------- */
    .store-badges { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
    .store-badges.center { justify-content: center; }
    .store-badge {
        display: inline-flex; align-items: center; gap: 14px; background: #000; color: #fff;
        border: 1px solid rgba(255,255,255,0.15); border-radius: 14px; padding: 14px 26px 14px 22px;
        text-decoration: none; transition: transform .15s ease, opacity .15s ease;
    }
    .store-badge:hover { transform: translateY(-2px); opacity: .92; }
    .store-badge svg { width: 30px; height: 30px; flex-shrink: 0; }
    .store-badge span { display: flex; flex-direction: column; line-height: 1.2; }
    .store-badge small { font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.75); }
    .store-badge strong { font-size: 21px; font-weight: 700; font-family: var(--sans); letter-spacing: -0.01em; }

    /* ---------- Footer ---------- */
    .site-footer { background: #fff; border-top: 1px solid var(--line); padding: 72px 0 32px; position: relative; overflow: hidden; }
    .site-footer .logo { color: var(--ink); }
    .footer-grid { display: grid; grid-template-columns: 1.4fr repeat(4, 1fr); gap: 32px; margin-bottom: 52px; }
    @media (max-width: 860px) { .footer-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 520px) { .footer-grid { grid-template-columns: 1fr; } }
    .footer-brand .logo { margin-bottom: 16px; }
    .footer-brand p { color: var(--slate); font-size: 14px; max-width: 280px; margin: 0 0 22px; line-height: 1.6; }
    .footer-social { display: flex; gap: 10px; }
    .footer-social a {
        width: 36px; height: 36px; border-radius: 10px; background: var(--mist);
        border: 1px solid var(--line); color: var(--slate);
        display: flex; align-items: center; justify-content: center; transition: background .15s, color .15s;
    }
    .footer-social a:hover { background: var(--mist-deep); color: var(--ink); }
    .footer-col h4 { font-family: var(--sans); font-size: 12.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; margin: 0 0 18px; color: var(--slate-light); }
    .footer-col ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 13px; }
    .footer-col a { font-size: 14.5px; text-decoration: none; color: var(--ink); font-weight: 500; transition: color .15s; }
    .footer-col a:hover { color: var(--green-deep); }
    .footer-bottom { border-top: 1px solid var(--line); padding-top: 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
    .footer-bottom p { margin: 0; font-size: 13px; color: var(--slate); }

    /* ---------- Misc shared ---------- */
    .pill { display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px 7px 8px; border-radius: 999px; background: #fff; color: var(--green-deep); font-weight: 700; font-size: 13px; border: 1px solid var(--line); box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
    .pill .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green); box-shadow: 0 0 0 3px rgba(0,160,91,0.18); }
    .form-field { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
    .form-field label { font-size: 13.5px; font-weight: 700; }
    .form-field input, .form-field textarea, .form-field select {
        font-family: var(--sans); font-size: 15px; padding: 13px 16px; border-radius: 12px;
        border: 1.5px solid var(--line); background: #fff; color: var(--ink); resize: vertical;
    }
    .form-field input:focus, .form-field textarea:focus, .form-field select:focus { outline: none; border-color: var(--ink); }
    .field-error { color: #C8372D; font-size: 13px; }
    .alert-success { background: #E9FBF4; border: 1px solid #B9EEDA; color: var(--green-deep); padding: 16px 20px; border-radius: 14px; font-weight: 600; font-size: 14.5px; margin-bottom: 28px; }

    /* ---------- Hero (centered, full-bleed mesh) ---------- */
    .hero-centered { position: relative; overflow: hidden; padding: 128px 0 96px; text-align: center; }
    .hero-centered::before {
        content: ""; position: absolute; inset: 0; z-index: -1;
        background:
            radial-gradient(circle at 15% 10%, rgba(62,220,180,0.28), transparent 42%),
            radial-gradient(circle at 85% 0%, rgba(58,76,242,0.22), transparent 45%),
            radial-gradient(circle at 50% 90%, rgba(255,182,72,0.14), transparent 40%),
            linear-gradient(180deg, #fff, #fff);
    }
    .hero-centered h1 { font-size: 68px; line-height: 1.04; margin: 28px auto 26px; max-width: 900px; }
    .hero-centered .lede { font-size: 20px; color: var(--slate); max-width: 560px; margin: 0 auto 40px; font-family: var(--sans); }
    @media (max-width: 780px) { .hero-centered h1 { font-size: 42px; } .hero-centered { padding: 100px 0 64px; } }
    @media (max-width: 480px) { .hero-centered h1 { font-size: 34px; } }
    .hero-centered .cta-row { justify-content: center; margin-bottom: 16px; }
    .hero-centered .cta-note { justify-content: center; }

    /* ---------- Product preview (big centered phone) ---------- */
    .preview-stage { position: relative; display: flex; justify-content: center; padding: 24px 0 8px; }
    .preview-glow {
        position: absolute; width: 520px; height: 520px; border-radius: 50%; top: -10%; left: 50%; transform: translateX(-50%);
        background: radial-gradient(circle, rgba(62,220,180,0.16), transparent 68%); z-index: 0; filter: blur(4px);
    }
    .preview-phone {
        position: relative; z-index: 1; width: 320px; border-radius: 46px; background: var(--ink); padding: 14px;
        box-shadow: 0 50px 100px -30px rgba(14,26,22,0.5), 0 14px 30px -14px rgba(14,26,22,0.3);
    }
    .preview-phone .screen { background: #fff; border-radius: 34px; overflow: hidden; }
    .preview-toast {
        position: absolute; z-index: 2; background: #fff; border-radius: 16px; padding: 14px 18px;
        box-shadow: 0 20px 40px -16px rgba(14,26,22,0.25); border: 1px solid var(--line);
        display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 700;
    }
    .preview-toast.t1 { top: 14%; left: calc(50% - 300px); }
    .preview-toast.t2 { bottom: 16%; right: calc(50% - 300px); }
    .preview-toast.t3 { top: 50%; left: calc(50% - 330px); }
    @media (max-width: 900px) { .preview-toast { display: none; } }

    /* ---------- Numbered steps ---------- */
    .steps { position: relative; display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; margin-top: 8px; }
    @media (max-width: 780px) { .steps { grid-template-columns: 1fr; gap: 48px; } }
    .steps::before, .steps::after {
        content: ""; position: absolute; top: 27px; height: 2px;
        -webkit-mask-image: repeating-linear-gradient(90deg, #000 0 8px, transparent 8px 16px);
        mask-image: repeating-linear-gradient(90deg, #000 0 8px, transparent 8px 16px);
    }
    .steps::before { left: calc(16.66% + 28px); right: calc(50% + 28px); background: linear-gradient(90deg, var(--hero-start), var(--hero-end)); }
    .steps::after { left: calc(50% + 28px); right: calc(16.66% + 28px); background: linear-gradient(90deg, var(--hero-end), var(--sun)); }
    @media (max-width: 780px) { .steps::before, .steps::after { display: none; } }
    .step { position: relative; text-align: center; }
    .step .num {
        width: 56px; height: 56px; border-radius: 50%; background: var(--ink); color: #fff;
        display: flex; align-items: center; justify-content: center; font-family: var(--serif); font-size: 22px;
        margin: 0 auto 22px; position: relative; z-index: 1; box-shadow: 0 10px 22px -8px rgba(14,26,22,0.35);
    }
    .step h3 { font-family: var(--sans); font-size: 17px; margin: 0 0 8px; }
    .step p { font-size: 14.5px; color: var(--slate); margin: 0; max-width: 260px; margin-inline: auto; }

    /* ---------- Dark section (contrast break) ---------- */
    section.dark { background: var(--ink); color: #fff; }
    section.dark .kicker { color: var(--hero-start); }
    section.dark .kicker::before { background: var(--hero-start); }
    section.dark .section-head p { color: rgba(255,255,255,0.6); }
    section.dark h2 { color: #fff; }
    section.dark .security-item { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.1); }
    section.dark .security-item:hover { border-color: rgba(255,255,255,0.25); box-shadow: none; }
    section.dark .security-item .icon { background: rgba(255,255,255,0.08); }
    section.dark .security-item h3 { color: #fff; }
    section.dark .security-item p { color: rgba(255,255,255,0.6); }
    section.dark .btn-ghost { background: transparent; border-color: rgba(255,255,255,0.25); color: #fff; }
    section.dark .btn-ghost:hover { border-color: #fff; }
</style>

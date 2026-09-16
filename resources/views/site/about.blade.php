<x-site-layout :title="site_setting('seo_about_title')" :description="site_setting('seo_about_description')">

    @php
        $icons = [
            'mobile' => '<rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 18h2"/>',
            'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
            'link' => '<path d="M9 15l6-6"/><path d="M11 6l1-1a4 4 0 0 1 6 6l-1 1"/><path d="M13 18l-1 1a4 4 0 0 1-6-6l1-1"/>',
            'tag' => '<path d="M20.5 12.7 12.8 20.4a2 2 0 0 1-2.8 0l-6.4-6.4a2 2 0 0 1 0-2.8L11.3 3.5A2 2 0 0 1 12.7 3H19a1 1 0 0 1 1 1v6.3a2 2 0 0 1-.5 1.4Z"/><circle cx="15.5" cy="7.5" r="1.5"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
            'chat' => '<path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.55L3 20l1.1-5.3A8.5 8.5 0 1 1 21 11.5Z"/>',
        ];
        $iconSvg = fn (string $key, int $size = 22) => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'.$icons[$key].'</svg>';
    @endphp

    <section class="page-hero">
        <div class="wrap reveal" style="position:relative;">
            <span class="hero-quote-mark" aria-hidden="true">&ldquo;</span>
            <h1>{{ site_content('about', 'hero_title') }}</h1>
            <p class="lede">{{ site_content('about', 'hero_subtitle') }}</p>
        </div>
    </section>

    <section class="tight">
        <div class="wrap">
            <div class="mission-block reveal">
                <div class="mission-mark">"</div>
                <p class="mission-lead">{{ site_content('about', 'mission_lead') }}</p>
                <p class="mission-sub">{{ site_content('about', 'mission_sub') }}</p>
            </div>
        </div>
    </section>

    <section class="alt">
        <div class="wrap">
            <div class="section-head center reveal">
                <h2>{{ site_content('about', 'values_heading') }}</h2>
            </div>
            <div class="value-list">
                <div class="value-row reveal">
                    <div class="value-icon" style="background:var(--green);">{!! $iconSvg('mobile') !!}</div>
                    <div class="value-text">
                        <h3>Mobile-first, vraiment</h3>
                        <p>Pensée pour un usage entièrement mobile dès le premier écran, pas une adaptation d'un service pensé pour le web.</p>
                    </div>
                </div>
                <div class="value-row reveal">
                    <div class="value-icon" style="background:var(--hero-end);">{!! $iconSvg('eye') !!}</div>
                    <div class="value-text">
                        <h3>Transparence par défaut</h3>
                        <p>Frais, taux de change et statut de chaque opération sont toujours visibles avant confirmation.</p>
                    </div>
                </div>
                <div class="value-row reveal">
                    <div class="value-icon" style="background:var(--sun);">{!! $iconSvg('link') !!}</div>
                    <div class="value-text">
                        <h3>Interopérable</h3>
                        <p>Compatible avec les opérateurs mobile money déjà utilisés au quotidien, plutôt que de créer un écosystème fermé.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="tight">
        <div class="wrap">
            <div class="section-head center reveal">
                <h2>{{ site_content('about', 'commitments_heading') }}</h2>
                <p>{{ site_content('about', 'commitments_subheading') }}</p>
            </div>
            <div class="bento" style="grid-template-columns:repeat(2,1fr);grid-auto-rows:auto;">
                <div class="card reveal"><div class="icon" style="background:var(--green);">{!! $iconSvg('tag', 20) !!}</div><h3>Frais annoncés d'avance</h3><p>Aucun frais n'apparaît après coup : tout est affiché avant que vous confirmiez.</p></div>
                <div class="card reveal"><div class="icon" style="background:var(--hero-end);">{!! $iconSvg('clock', 20) !!}</div><h3>Un statut honnête</h3><p>Une opération reste "en cours" tant qu'elle n'est pas confirmée — jamais annoncée en avance.</p></div>
                <div class="card reveal"><div class="icon" style="background:var(--sun);">{!! $iconSvg('lock', 20) !!}</div><h3>Vos données protégées</h3><p>Chiffrement, aucune donnée sensible journalisée, contrôle total de vos sessions.</p></div>
                <div class="card reveal"><div class="icon" style="background:var(--pink);">{!! $iconSvg('chat', 20) !!}</div><h3>Un support à l'écoute</h3><p>Une question, un souci — notre équipe vous répond directement.</p></div>
            </div>
            <style>@media (max-width: 640px) { .bento[style*="repeat(2"] { grid-template-columns: 1fr !important; } }</style>
        </div>
    </section>

    <section class="tight">
        <div class="wrap">
            <div class="cta-band reveal-scale">
                <h2>{{ site_content('about', 'cta_heading') }}</h2>
                <p>{{ site_content('about', 'cta_subheading') }}</p>
                <div class="cta-row">
                    <a href="{{ route('site.contact') }}" class="btn btn-primary">Nous contacter</a>
                    <a href="{{ route('site.features') }}" class="btn btn-ghost">Voir les fonctionnalités</a>
                </div>
            </div>
        </div>
    </section>

</x-site-layout>

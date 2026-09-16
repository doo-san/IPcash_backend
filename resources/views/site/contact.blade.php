<x-site-layout :title="site_setting('seo_contact_title')" :description="site_setting('seo_contact_description')">

    @php
        $icons = [
            'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        ];
        $iconSvg = fn (string $key, int $size = 20, string $stroke = '#fff') => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="'.$stroke.'" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$icons[$key].'</svg>';
    @endphp

    <section class="page-hero" style="padding-bottom:0;text-align:center;">
        <div class="wrap reveal">
            <h1 style="margin-inline:auto;">{{ site_content('contact', 'hero_title') }}</h1>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="contact-grid" style="display:grid;grid-template-columns:0.85fr 1.3fr;gap:32px;align-items:stretch;">
                <div class="reveal contact-panel">
                    <h2>{{ site_content('contact', 'panel_heading') }}</h2>
                    <p class="desc">{{ site_content('contact', 'panel_subheading') }}</p>
                    <div class="contact-item">
                        <div class="value-icon" style="background:var(--green);">{!! $iconSvg('mail') !!}</div>
                        <div>
                            <h3>Par email</h3>
                            <p>Le formulaire ci-contre associe automatiquement votre adresse pour qu'on puisse vous répondre.</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="value-icon" style="background:var(--hero-end);">{!! $iconSvg('clock') !!}</div>
                        <div>
                            <h3>Délai de réponse</h3>
                            <p>Nous répondons généralement sous quelques jours ouvrés.</p>
                        </div>
                    </div>
                </div>

                <div class="reveal">
                    @if (session('contactSent'))
                        <div class="alert-success">Message envoyé — merci, nous vous répondrons bientôt.</div>
                    @endif
                    <div class="contact-form-card">
                        <form method="POST" action="{{ route('site.contact.store') }}">
                            @csrf
                            <div class="form-row-2">
                                <div class="form-field">
                                    <label for="name">Nom complet</label>
                                    <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name') <span class="field-error">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-field">
                                    <label for="email">E-mail</label>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="form-field">
                                <label for="subject">Sujet</label>
                                <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required>
                                @error('subject') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="form-field">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
                                @error('message') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                                Envoyer le message
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <style>@media (max-width: 780px) { .contact-grid { grid-template-columns: 1fr !important; } }</style>
        </div>
    </section>

</x-site-layout>

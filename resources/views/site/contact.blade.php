<x-site-layout :title="'Contact'" :description="'Contactez l’équipe IPCash pour toute question, partenariat ou remarque.'">

    <section class="page-hero" style="padding-bottom:0;">
        <div class="wrap reveal">
            <span class="kicker">Contact</span>
            <h1>Parlons-en.</h1>
            <p class="lede">Une question sur l'app, un partenariat, une remarque — écrivez-nous, nous vous répondrons dès que possible.</p>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div style="display:grid;grid-template-columns:1fr 1.3fr;gap:56px;align-items:flex-start;" class="contact-grid">
                <div class="reveal">
                    <h2 style="font-size:24px;margin:0 0 20px;">Autrement</h2>
                    <div style="display:flex;flex-direction:column;gap:20px;">
                        <div class="security-item" style="align-items:flex-start;">
                            <div class="icon">📩</div>
                            <div>
                                <h3>Par email</h3>
                                <p>Pour toute question générale, utilisez le formulaire ci-contre — un e-mail y sera associé pour vous répondre.</p>
                            </div>
                        </div>
                        <div class="security-item" style="align-items:flex-start;">
                            <div class="icon">🕑</div>
                            <div>
                                <h3>Délai de réponse</h3>
                                <p>Nous répondons généralement sous quelques jours ouvrés.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reveal">
                    @if (session('contactSent'))
                        <div class="alert-success">Message envoyé — merci, nous vous répondrons bientôt.</div>
                    @endif
                    <form method="POST" action="{{ route('site.contact.store') }}" style="background:#fff;border:1px solid var(--line);border-radius:var(--radius-lg);padding:32px;">
                        @csrf
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
                        <div class="form-field">
                            <label for="subject">Sujet</label>
                            <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required>
                            @error('subject') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-field">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="5" required>{{ old('message') }}</textarea>
                            @error('message') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">Envoyer le message</button>
                    </form>
                </div>
            </div>
            <style>@media (max-width: 780px) { .contact-grid { grid-template-columns: 1fr !important; } }</style>
        </div>
    </section>

</x-site-layout>

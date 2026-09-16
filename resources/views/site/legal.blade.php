<x-site-layout :title="$page->title">

    <section class="page-hero" style="padding-bottom:32px;">
        <div class="wrap reveal">
            <h1>{{ $page->title }}</h1>
        </div>
    </section>

    <section class="tight">
        <div class="wrap-narrow reveal legal-content">
            {{-- Contenu rédigé depuis l'admin (Filament RichEditor,
                 App\Filament\Resources\LegalPageResource) : seuls les
                 comptes admin peuvent l'éditer, non échappé volontairement
                 pour conserver la mise en forme (titres, listes...). --}}
            {!! $page->content !!}
        </div>
    </section>

</x-site-layout>

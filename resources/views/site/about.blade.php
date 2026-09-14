<x-site-layout :title="'À propos'" :description="'La mission d’IPCash : rendre les services financiers du quotidien accessibles depuis un seul téléphone, partout en UEMOA.'">

    <section class="page-hero">
        <div class="wrap reveal">
            <span class="kicker">À propos</span>
            <h1>Simplifier l'argent, pour tout le monde.</h1>
            <p class="lede">IPCash est né d'un constat simple : gérer son argent au quotidien demande encore trop souvent de jongler entre plusieurs opérateurs, agences et applications.</p>
        </div>
    </section>

    <section class="tight">
        <div class="wrap-narrow reveal">
            <h2 style="font-size:28px;margin:0 0 18px;">Notre mission</h2>
            <p style="color:var(--slate);font-size:17px;margin:0 0 20px;font-family:var(--sans);">
                Nous construisons une application unique qui réunit les opérations financières du quotidien —
                transférer, épargner, payer, changer de devise — pour la zone UEMOA, en commençant par le Sénégal.
                L'objectif n'est pas d'ajouter une nouvelle app à la pile, mais de remplacer plusieurs applications,
                agences et files d'attente par une seule.
            </p>
            <p style="color:var(--slate);font-size:17px;margin:0;font-family:var(--sans);">
                Nous croyons qu'un service financier doit être aussi simple à utiliser qu'à comprendre :
                pas de jargon, pas de frais cachés, et un statut clair sur chaque opération — en cours,
                confirmée ou échouée, jamais autre chose.
            </p>
        </div>
    </section>

    <section class="alt">
        <div class="wrap">
            <div class="section-head center reveal">
                <span class="kicker" style="justify-content:center;">Notre approche</span>
                <h2>Trois principes qui guident chaque décision</h2>
            </div>
            <div class="bento" style="grid-template-columns:repeat(3,1fr);grid-auto-rows:210px;">
                <div class="card reveal"><div class="icon">📱</div><h3>Mobile-first, vraiment</h3><p>Pensée pour un usage entièrement mobile dès le premier écran, pas une adaptation d'un service pensé pour le web.</p></div>
                <div class="card reveal"><div class="icon">🔍</div><h3>Transparence par défaut</h3><p>Frais, taux de change et statut de chaque opération sont toujours visibles avant confirmation.</p></div>
                <div class="card reveal"><div class="icon">🤝</div><h3>Interopérable</h3><p>Compatible avec les opérateurs mobile money déjà utilisés au quotidien, plutôt que de créer un écosystème fermé.</p></div>
            </div>
            <style>@media (max-width: 860px) { .bento[style*="repeat(3"] { grid-template-columns: 1fr !important; } }</style>
        </div>
    </section>

    <section class="tight">
        <div class="wrap">
            <div class="cta-band reveal-scale">
                <h2>Envie d'en discuter ?</h2>
                <p>Une question, un partenariat, une remarque — écrivez-nous.</p>
                <div class="cta-row">
                    <a href="{{ route('site.contact') }}" class="btn btn-primary">Nous contacter</a>
                    <a href="{{ route('site.features') }}" class="btn btn-ghost">Voir les fonctionnalités</a>
                </div>
            </div>
        </div>
    </section>

</x-site-layout>

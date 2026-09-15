<?php

use App\Http\Controllers\ContactController;
use App\Models\MobileMoneyProvider;
use App\Models\Transaction;
use App\Services\Wave\WaveCheckoutFinalizer;
use App\Services\Wave\WaveClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// Site public de présentation d'IPCash — CSS entièrement inline (pas de
// classes Tailwind/build Vite), volontairement : évite de dépendre d'un
// build Node en production pour ces pages. Voir
// resources/views/components/site-layout.blade.php pour le gabarit commun.
Route::view('/', 'site.home')->name('site.home');
Route::view('/fonctionnalites', 'site.features')->name('site.features');
Route::view('/securite', 'site.security')->name('site.security');
Route::view('/a-propos', 'site.about')->name('site.about');
Route::get('/contact', fn () => view('site.contact'))->name('site.contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('site.contact.store');

// Page affichée après un paiement OM Pay (succès ou annulation) — l'app
// intercepte la navigation de la WebView vers cette URL pour fermer
// l'écran et revenir au flux normal (voir OrangeMoneyPaymentWebViewScreen
// côté Flutter) ; ce rendu HTML n'est vu que si l'utilisateur ferme
// manuellement l'onglet au lieu de laisser l'app faire.
Route::get('/orange-money/return', function (Request $request) {
    return view('orange-money.return', ['status' => $request->query('status', 'success')]);
})->name('orange-money.return');

// Même page (générique, pas de mention d'Orange dedans) réutilisée pour
// Wave — pas besoin d'une deuxième vue pour un texte identique.
//
// Filet de sécurité : avant d'afficher la page, si `ref` est présent
// (ajouté par nous-mêmes à successUrl/errorUrl dans CashioController, pas
// fourni par Wave), on vérifie activement le statut de la session Wave
// (GET /v1/checkout/sessions/{id}) et on finalise la transaction si elle
// est encore `pending` — sans dépendre du webhook, qui peut ne jamais
// arriver (tunnel de dev mort, retard réseau...). Le webhook reste la voie
// la plus rapide quand il fonctionne ; ceci ne fait que rattraper le cas
// où il échoue.
Route::get('/wave/return', function (Request $request) {
    $reference = $request->query('ref');
    if (is_string($reference)) {
        $transaction = Transaction::where('reference', $reference)->where('type', 'cashIn')->first();
        $provider = MobileMoneyProvider::query()->get()->first(fn (MobileMoneyProvider $p) => $p->isWave());

        if ($transaction && $transaction->status->value === 'pending' && $transaction->provider_reference && $provider) {
            try {
                $session = (new WaveClient($provider))->getCheckoutSessionStatus($transaction->provider_reference);
                app(WaveCheckoutFinalizer::class)->finalize(
                    $transaction,
                    $session['paymentStatus'],
                    $session['lastPaymentErrorMessage'],
                );
            } catch (Throwable $e) {
                Log::warning('[wave] vérification de statut au retour échouée', ['error' => $e->getMessage()]);
            }
        }
    }

    return view('orange-money.return', ['status' => $request->query('status', 'success')]);
})->name('wave.return');

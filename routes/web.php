<?php

use App\Http\Controllers\ContactController;
use Illuminate\Http\Request;
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
Route::get('/wave/return', function (Request $request) {
    return view('orange-money.return', ['status' => $request->query('status', 'success')]);
})->name('wave.return');

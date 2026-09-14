<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Pas de vitrine publique — seul l'admin (/admin, Filament) sert à
// quelque chose ici. Évite aussi de dépendre d'un build Node/Vite en
// production pour une page qui n'était jamais utilisée (voir welcome.blade.php,
// supprimée).
Route::get('/', fn () => redirect('/admin'));

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

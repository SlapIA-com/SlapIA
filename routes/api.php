<?php

use App\Http\Controllers\Api\N8nArticleController;
use App\Http\Middleware\EnsureN8nApiKey;
use Illuminate\Support\Facades\Route;

/**
 * API interne, appelée uniquement par le workflow n8n de génération
 * d'articles (voir N8nArticleController). `recent` est public (données
 * déjà publiques, identiques à /blog) ; `store` (création) est protégée
 * par une clé partagée — voir EnsureN8nApiKey et N8N_ARTICLES_API_KEY
 * dans .env.
 *
 * `recent` n'a pas de clé (elle doit rester appelable simplement, comme
 * /blog) : le throttle limite juste le nombre d'appels par adresse IP,
 * pour qu'un robot ou un script ne puisse pas la bombarder. 30/min est
 * très large par rapport à l'usage réel (n8n l'appelle une poignée de
 * fois par exécution de workflow, pas 30 fois par minute).
 */
Route::prefix('n8n')->group(function () {
    Route::get('/articles/recent', [N8nArticleController::class, 'recent'])
        ->middleware('throttle:30,1');
    Route::post('/articles', [N8nArticleController::class, 'store'])
        ->middleware(EnsureN8nApiKey::class);
});

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
 */
Route::prefix('n8n')->group(function () {
    Route::get('/articles/recent', [N8nArticleController::class, 'recent']);
    Route::post('/articles', [N8nArticleController::class, 'store'])
        ->middleware(EnsureN8nApiKey::class);
});

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RssSubscriber;
use Illuminate\Http\JsonResponse;

/**
 * API interne consommée par le workflow n8n de newsletter (nœud "Fetch
 * Subscribers"). Remplace l'ancienne base Notion "RSS Subscriber" : les
 * abonnés sont gérés depuis l'admin SlapIA (voir AdminController) et
 * stockés dans la table MySQL rss_subscriber depuis le début (voir
 * RssSubscriber) — il ne restait que ce nœud n8n encore branché sur Notion.
 *
 * Contient des adresses email (donnée personnelle) : contrairement à
 * `recent()` de N8nArticleController, cette route est protégée par la
 * même clé partagée que la création d'article — voir EnsureN8nApiKey et
 * N8N_ARTICLES_API_KEY dans .env.
 */
class N8nSubscriberController extends Controller
{
    public function index(): JsonResponse
    {
        $subscribers = RssSubscriber::orderBy('date_creation')
            ->get(['email', 'date_creation']);

        return response()->json($subscribers->map(fn (RssSubscriber $s) => [
            'email' => $s->email,
            'date_creation' => optional($s->date_creation)->toIso8601String(),
        ]));
    }
}

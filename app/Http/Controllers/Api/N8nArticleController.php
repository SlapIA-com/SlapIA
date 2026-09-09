<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\CommonMark\CommonMarkConverter;

/**
 * API interne consommée par le workflow n8n de génération automatique
 * d'articles (basculé de Notion vers MySQL le 7 septembre 2026 — voir le
 * workflow "Split & Publish"). Protégée par une clé partagée
 * (EnsureN8nApiKey), sauf `recent()` qui n'expose que des données déjà
 * publiques (identiques à /blog).
 */
class N8nArticleController extends Controller
{
    /**
     * Les 6 (par défaut) derniers articles publiés — utilisé par n8n pour
     * (a) éviter de refaire un sujet déjà traité récemment (nœud "Title
     * History") et (b) la section "à lire aussi" de la newsletter.
     *
     * `name` duplique `title` : compatibilité avec le nœud "Send
     * Newsletter" existant, qui lisait `.name` (forme aplatie que
     * renvoyait l'ancien nœud Notion) — évite de toucher au template HTML
     * de la newsletter.
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 6), 20);

        $articles = Article::orderByDesc('published_at')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'excerpt', 'image', 'published_at']);

        return response()->json($articles->map(fn (Article $a) => [
            'id' => (string) $a->id,
            'title' => $a->title,
            'name' => $a->title,
            'slug' => $a->slug,
            'excerpt' => $a->excerpt ?? '',
            'image' => $a->image,
            'published_at' => $a->published_at->toIso8601String(),
            'url' => url('/blog/'.$a->slug),
        ]));
    }

    /**
     * Crée un article. Le contenu arrive en Markdown simple (titres ##,
     * paragraphes séparés par une ligne vide — c'est le format produit par
     * le prompt du "Basic LLM Chain" n8n) et est converti en HTML ici,
     * puisque Blog/Show.tsx affiche `content` via dangerouslySetInnerHTML
     * (même convention que les articles importés de Notion et que la
     * saisie manuelle dans l'admin).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'string', 'max:5000', 'url'],
            'published_at' => ['nullable', 'date'],
        ]);

        $converter = new CommonMarkConverter();
        $html = (string) $converter->convert($data['content']);

        $article = Article::create([
            'title' => $data['title'],
            'slug' => Article::uniqueSlugFor($data['title']),
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $html,
            'image' => $data['image'] ?? null,
            'published_at' => $data['published_at'] ?? now(),
        ]);

        return response()->json([
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'url' => url('/blog/'.$article->slug),
        ], 201);
    }
}

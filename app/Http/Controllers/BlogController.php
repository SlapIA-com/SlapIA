<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Basculé de Notion vers MySQL le 6 septembre 2026 (voir migration
 * create_articles_table et ImportNotionBlogArticles pour l'historique).
 * Même forme de données que l'ancien NotionBlogService pour ne rien changer
 * côté React (Blog/Index.tsx, Blog/Show.tsx) : id, title, excerpt, date,
 * image, slug, content.
 */
class BlogController extends Controller
{
    public function index(): Response
    {
        $articles = Article::orderByDesc('published_at')->get()->map(fn (Article $a) => [
            'id' => (string) $a->id,
            'title' => $a->title,
            'excerpt' => $a->excerpt ?? '',
            'date' => $a->published_at->toIso8601String(),
            'image' => $a->image,
            'slug' => $a->slug,
        ]);

        return Inertia::render('Blog/Index', [
            'articles' => $articles,
        ]);
    }

    public function show(string $slug): Response
    {
        $article = Article::where('slug', $slug)->first();

        abort_if($article === null, 404);

        return Inertia::render('Blog/Show', [
            'article' => [
                'id' => (string) $article->id,
                'title' => $article->title,
                'excerpt' => $article->excerpt ?? '',
                'date' => $article->published_at->toIso8601String(),
                'image' => $article->image,
                'slug' => $article->slug,
                'content' => $article->content ?? '',
            ],
        ]);
    }
}

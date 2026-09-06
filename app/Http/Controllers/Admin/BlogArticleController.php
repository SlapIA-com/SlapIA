<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gestion manuelle des articles de blog depuis l'admin, en attendant que le
 * workflow n8n qui publiait sur Notion soit réécrit pour écrire ici (voir
 * migration create_articles_table et ImportNotionBlogArticles).
 */
class BlogArticleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:2048'],
            'published_at' => ['nullable', 'date'],
        ]);

        $slug = Article::slugify($data['title']);
        $base = $slug;
        $i = 2;
        while (Article::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        Article::create([
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'] ?? null,
            'image' => $data['image'] ?? null,
            'published_at' => $data['published_at'] ?? now(),
        ]);

        return back()->with('success', 'Article créé.');
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:2048'],
            'published_at' => ['nullable', 'date'],
        ]);

        $article->update($data);

        return back()->with('success', 'Article mis à jour.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return back()->with('success', 'Article supprimé.');
    }
}

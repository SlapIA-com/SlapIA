<?php

namespace App\Http\Controllers;

use App\Services\Notion\NotionBlogService;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(NotionBlogService $blog): Response
    {
        return Inertia::render('Blog/Index', [
            'articles' => $blog->listArticles(),
        ]);
    }

    public function show(string $slug, NotionBlogService $blog): Response
    {
        $article = $blog->findBySlug($slug);

        abort_if($article === null, 404);

        return Inertia::render('Blog/Show', [
            'article' => $article,
        ]);
    }
}

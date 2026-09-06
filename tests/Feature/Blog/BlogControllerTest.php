<?php

namespace Tests\Feature\Blog;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Non-régression de la bascule Notion -> MySQL du 6 septembre 2026 : le
 * blog public doit continuer à fonctionner exactement pareil, juste avec
 * une source de données différente.
 */
class BlogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_published_articles(): void
    {
        Article::factory()->create(['title' => 'Premier article']);

        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Index')
            ->has('articles', 1)
            ->where('articles.0.title', 'Premier article')
        );
    }

    public function test_show_returns_article_by_slug(): void
    {
        $article = Article::factory()->create(['title' => 'Mon article', 'content' => '<p>Contenu</p>']);

        $response = $this->get("/blog/{$article->slug}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Show')
            ->where('article.title', 'Mon article')
            ->where('article.content', '<p>Contenu</p>')
        );
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $response = $this->get('/blog/slug-inexistant');

        $response->assertNotFound();
    }
}

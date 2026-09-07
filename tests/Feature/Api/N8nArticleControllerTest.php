<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Couvre l'API interne consommée par le workflow n8n de génération
 * d'articles (voir N8nArticleController) : c'est la seule surface
 * d'écriture de tout le projet exposée sans session/CSRF, protégée
 * uniquement par la clé partagée X-N8N-Key (EnsureN8nApiKey) — donc la
 * plus importante à couvrir par des tests.
 *
 * La clé de test (N8N_ARTICLES_API_KEY) est définie dans phpunit.xml ;
 * on la relit via config() plutôt que de la recopier en dur ici, pour
 * qu'un changement de valeur dans phpunit.xml n'oblige pas à toucher ce
 * fichier.
 */
class N8nArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validApiKey(): string
    {
        return (string) config('services.n8n.articles_api_key');
    }

    // ------------------------------------------------------------------
    // GET /api/n8n/articles/recent — publique, pas de clé requise
    // ------------------------------------------------------------------

    public function test_recent_is_accessible_without_api_key(): void
    {
        Article::factory()->create();

        $response = $this->getJson('/api/n8n/articles/recent');

        $response->assertOk();
    }

    public function test_recent_returns_articles_ordered_by_published_at_desc(): void
    {
        $oldest = Article::factory()->create(['title' => 'Le plus vieux', 'published_at' => now()->subDays(10)]);
        $newest = Article::factory()->create(['title' => 'Le plus recent', 'published_at' => now()->subDay()]);
        $middle = Article::factory()->create(['title' => 'Au milieu', 'published_at' => now()->subDays(5)]);

        $response = $this->getJson('/api/n8n/articles/recent');

        $response->assertOk();
        $response->assertJsonPath('0.title', $newest->title);
        $response->assertJsonPath('1.title', $middle->title);
        $response->assertJsonPath('2.title', $oldest->title);
    }

    public function test_recent_response_shape_matches_n8n_contract(): void
    {
        $article = Article::factory()->create([
            'title' => 'Mon article',
            'excerpt' => 'Un extrait',
            'image' => 'https://res.cloudinary.com/demo/image.jpg',
        ]);

        $response = $this->getJson('/api/n8n/articles/recent');

        $response->assertOk();
        $response->assertJsonPath('0.id', (string) $article->id);
        $response->assertJsonPath('0.title', 'Mon article');
        // "name" duplique "title" : compatibilité avec l'ancien nœud
        // Notion "Send Newsletter" qui lisait .name (voir le commentaire
        // dans N8nArticleController::recent()).
        $response->assertJsonPath('0.name', 'Mon article');
        $response->assertJsonPath('0.slug', $article->slug);
        $response->assertJsonPath('0.excerpt', 'Un extrait');
        $response->assertJsonPath('0.image', 'https://res.cloudinary.com/demo/image.jpg');
        $response->assertJsonPath('0.url', url('/blog/'.$article->slug));
        $response->assertJsonStructure([['published_at']]);
    }

    public function test_recent_defaults_to_six_articles(): void
    {
        Article::factory()->count(8)->create();

        $response = $this->getJson('/api/n8n/articles/recent');

        $response->assertOk();
        $response->assertJsonCount(6);
    }

    public function test_recent_respects_limit_query_param(): void
    {
        Article::factory()->count(8)->create();

        $response = $this->getJson('/api/n8n/articles/recent?limit=3');

        $response->assertOk();
        $response->assertJsonCount(3);
    }

    public function test_recent_limit_is_capped_at_twenty(): void
    {
        Article::factory()->count(25)->create();

        $response = $this->getJson('/api/n8n/articles/recent?limit=999');

        $response->assertOk();
        $response->assertJsonCount(20);
    }

    // ------------------------------------------------------------------
    // POST /api/n8n/articles — protégée par X-N8N-Key
    // ------------------------------------------------------------------

    public function test_store_rejects_request_without_api_key(): void
    {
        $response = $this->postJson('/api/n8n/articles', [
            'title' => 'Un titre',
            'content' => 'Un contenu.',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('articles', 0);
    }

    public function test_store_rejects_wrong_api_key(): void
    {
        $response = $this->postJson('/api/n8n/articles', [
            'title' => 'Un titre',
            'content' => 'Un contenu.',
        ], ['X-N8N-Key' => 'mauvaise-cle']);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('articles', 0);
    }

    public function test_store_creates_article_with_valid_api_key(): void
    {
        $response = $this->postJson('/api/n8n/articles', [
            'title' => 'Groq leve 350M$',
            'excerpt' => 'Un extrait court.',
            'content' => "## Contexte\n\nUn paragraphe de contenu.",
            'image' => 'https://res.cloudinary.com/demo/image.jpg',
        ], ['X-N8N-Key' => $this->validApiKey()]);

        $response->assertCreated();
        $response->assertJsonStructure(['id', 'slug', 'title', 'url']);

        $this->assertDatabaseHas('articles', [
            'title' => 'Groq leve 350M$',
            'excerpt' => 'Un extrait court.',
            'image' => 'https://res.cloudinary.com/demo/image.jpg',
        ]);
    }

    public function test_store_converts_markdown_content_to_html(): void
    {
        $this->postJson('/api/n8n/articles', [
            'title' => 'Article avec markdown',
            'content' => "## Un titre de section\n\nUn paragraphe.",
        ], ['X-N8N-Key' => $this->validApiKey()])->assertCreated();

        $article = Article::where('title', 'Article avec markdown')->firstOrFail();

        $this->assertStringContainsString('<h2>Un titre de section</h2>', $article->content);
        $this->assertStringContainsString('<p>Un paragraphe.</p>', $article->content);
    }

    public function test_store_generates_unique_slug_on_title_collision(): void
    {
        // Slug explicite (pas celui, avec suffixe aléatoire, que mettrait la
        // factory par défaut) pour garantir une vraie collision de slug.
        Article::factory()->create(['title' => 'Sujet populaire', 'slug' => 'sujet-populaire']);

        $response = $this->postJson('/api/n8n/articles', [
            'title' => 'Sujet populaire',
            'content' => 'Un autre contenu.',
        ], ['X-N8N-Key' => $this->validApiKey()]);

        $response->assertCreated();
        $response->assertJsonPath('slug', 'sujet-populaire-2');
    }

    public function test_store_defaults_published_at_to_now_when_absent(): void
    {
        $response = $this->postJson('/api/n8n/articles', [
            'title' => 'Article sans date',
            'content' => 'Contenu.',
        ], ['X-N8N-Key' => $this->validApiKey()]);

        $response->assertCreated();

        $article = Article::where('title', 'Article sans date')->firstOrFail();
        $this->assertTrue($article->published_at->diffInMinutes(now()) < 1);
    }

    public function test_store_requires_title_and_content(): void
    {
        $response = $this->postJson('/api/n8n/articles', [
            'excerpt' => 'Un extrait sans titre ni contenu.',
        ], ['X-N8N-Key' => $this->validApiKey()]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'content']);
    }

    public function test_store_rejects_invalid_image_url(): void
    {
        $response = $this->postJson('/api/n8n/articles', [
            'title' => 'Article avec image invalide',
            'content' => 'Contenu.',
            'image' => 'pas-une-url',
        ], ['X-N8N-Key' => $this->validApiKey()]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }
}

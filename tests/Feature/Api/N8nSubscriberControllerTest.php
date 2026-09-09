<?php

namespace Tests\Feature\Api;

use App\Models\RssSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Couvre l'API interne consommée par le workflow n8n de newsletter (nœud
 * "Fetch Subscribers", remplace l'ancienne base Notion). Contient des
 * emails (donnée personnelle) : contrairement à /api/n8n/articles/recent,
 * cette route exige la clé partagée X-N8N-Key.
 */
class N8nSubscriberControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validApiKey(): string
    {
        return (string) config('services.n8n.articles_api_key');
    }

    public function test_index_rejects_request_without_api_key(): void
    {
        RssSubscriber::create(['email' => 'quelqu-un@example.com']);

        $response = $this->getJson('/api/n8n/subscribers');

        $response->assertUnauthorized();
    }

    public function test_index_rejects_wrong_api_key(): void
    {
        $response = $this->getJson('/api/n8n/subscribers', ['X-N8N-Key' => 'mauvaise-cle']);

        $response->assertUnauthorized();
    }

    public function test_index_returns_subscribers_with_valid_api_key(): void
    {
        RssSubscriber::create(['email' => 'alice@example.com']);
        RssSubscriber::create(['email' => 'bob@example.com']);

        $response = $this->getJson('/api/n8n/subscribers', ['X-N8N-Key' => $this->validApiKey()]);

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['email' => 'alice@example.com']);
        $response->assertJsonFragment(['email' => 'bob@example.com']);
    }

    public function test_index_response_shape_has_email_and_date_creation(): void
    {
        RssSubscriber::create(['email' => 'alice@example.com']);

        $response = $this->getJson('/api/n8n/subscribers', ['X-N8N-Key' => $this->validApiKey()]);

        $response->assertOk();
        $response->assertJsonStructure([['email', 'date_creation']]);
    }

    public function test_index_returns_empty_array_when_no_subscribers(): void
    {
        $response = $this->getJson('/api/n8n/subscribers', ['X-N8N-Key' => $this->validApiKey()]);

        $response->assertOk();
        $response->assertJsonCount(0);
    }
}

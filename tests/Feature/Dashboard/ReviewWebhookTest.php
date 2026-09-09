<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Couvre l'appel au webhook n8n (N8N_AVIS_WEBHOOK_URL, event
 * "review_received") déclenché par DashboardController::updateReview —
 * remplace le déclencheur Notion "NEW Evaluation" (email de remerciement).
 */
class ReviewWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_a_review_sends_thank_you_webhook(): void
    {
        config(['services.n8n.avis_webhook_url' => 'https://n8n.example.com/webhook/avis']);
        Http::fake();

        $compte = Compte::factory()->create(['email' => 'client@example.com']);
        Client::factory()->for($compte)->create(['nom_complet' => 'Alice Client']);

        $this->actingAs($compte)->post('/dashboard/review', [
            'commentaire' => 'Très satisfaite du service.',
            'satisfaction' => 5,
        ])->assertRedirect();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://n8n.example.com/webhook/avis'
                && $request['event'] === 'review_received'
                && $request['email'] === 'client@example.com'
                && $request['name'] === 'Alice Client'
                && $request['satisfaction'] === 5;
        });
    }

    public function test_submitting_a_review_still_succeeds_without_webhook_configured(): void
    {
        config(['services.n8n.avis_webhook_url' => null]);
        Http::fake();

        $compte = Compte::factory()->create();
        Client::factory()->for($compte)->create();

        $this->actingAs($compte)->post('/dashboard/review', [
            'commentaire' => 'Très bien.',
            'satisfaction' => 4,
        ])->assertRedirect();

        Http::assertNothingSent();
        $this->assertDatabaseHas('avis_clients', ['satisfaction' => 4]);
    }
}

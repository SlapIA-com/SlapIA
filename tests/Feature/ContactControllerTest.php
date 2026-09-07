<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Couvre ContactController::store, notamment l'appel au webhook n8n
 * (N8N_CONTACT_WEBHOOK_URL) qui remplace le déclencheur Notion
 * "NEW Contact web" — voir config('services.n8n.contact_webhook_url').
 */
class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'firstname' => 'Alice',
            'lastname' => 'Martin',
            'email' => 'alice@example.com',
            'company' => 'Acme',
            'subject' => 'subject_1',
            'message' => 'Bonjour, je souhaite un renseignement.',
            'consent' => true,
        ], $overrides);
    }

    public function test_store_creates_contact_message(): void
    {
        $this->post('/contact', $this->validPayload())->assertRedirect();

        $this->assertDatabaseHas('contact_siteweb', [
            'prenom' => 'Alice',
            'nom' => 'Martin',
            'email' => 'alice@example.com',
            'prise_de_contact_ok' => false,
        ]);
    }

    public function test_store_calls_contact_webhook_when_configured(): void
    {
        config(['services.n8n.contact_webhook_url' => 'https://n8n.example.com/webhook/nouveau-contact']);
        Http::fake();

        $this->post('/contact', $this->validPayload());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://n8n.example.com/webhook/nouveau-contact'
                && $request['event'] === 'new_contact'
                && $request['email'] === 'alice@example.com'
                && $request['prenom'] === 'Alice';
        });
    }

    public function test_store_does_not_fail_when_webhook_not_configured(): void
    {
        config(['services.n8n.contact_webhook_url' => null]);
        Http::fake();

        $this->post('/contact', $this->validPayload())->assertRedirect();

        Http::assertNothingSent();
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_store_still_succeeds_when_webhook_call_fails(): void
    {
        config(['services.n8n.contact_webhook_url' => 'https://n8n.example.com/webhook/nouveau-contact']);
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Le webhook n8n est injoignable.');
        });

        $this->post('/contact', $this->validPayload())->assertRedirect();

        $this->assertSame(1, ContactMessage::count());
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Compte;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Couvre AdminController::updateContactStatus / sendRegistrationInvite /
 * requestReview — la partie admin qui remplace le suivi manuel fait avant
 * dans la base Notion "ERP" (voir aussi ContactControllerTest).
 */
class AdminContactTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): Compte
    {
        $compte = Compte::factory()->create();
        Client::factory()->for($compte)->admin()->create();

        return $compte;
    }

    public function test_checking_prise_de_contact_ok_creates_account_and_sends_invite(): void
    {
        config(['services.n8n.auth_webhook_url' => 'https://n8n.example.com/webhook/auth']);
        Http::fake();

        $admin = $this->actingAsAdmin();
        $contact = ContactMessage::create([
            'prenom' => 'Bob',
            'nom' => 'Durand',
            'email' => 'bob@example.com',
            'sujet' => 'Autre question',
            'message' => 'Un message.',
            'prise_de_contact_ok' => false,
            'date_creation' => now(),
        ]);

        $response = $this->actingAs($admin)->patch("/admin/contacts/{$contact->id}", [
            'prise_de_contact_ok' => true,
            'type_client' => 'Particulier',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contact_siteweb', ['id' => $contact->id, 'prise_de_contact_ok' => true]);

        $compte = Compte::where('email', 'bob@example.com')->first();
        $this->assertNotNull($compte, 'Le compte aurait dû être créé automatiquement.');
        $this->assertNotNull($compte->client, 'Le client associé aurait dû être créé.');
        $this->assertSame('Bob Durand', $compte->client->nom_complet);
        $this->assertSame('Particulier', $compte->client->type_client);
        $this->assertSame('particulier', $compte->client->role());
        $this->assertNotNull($compte->reset_token);

        Http::assertSent(function ($request) use ($compte) {
            return $request->url() === 'https://n8n.example.com/webhook/auth'
                && $request['event'] === 'welcome'
                && $request['email'] === $compte->email
                && str_contains($request['reset_url'], 'email='.urlencode($compte->email));
        });
    }

    /**
     * Régression : sans type_client, le compte auto-créé se retrouvait avec
     * type_client = NULL, ce que Client::role() interprète comme "admin".
     * La validation doit bloquer ce cas plutôt que de créer un admin par défaut.
     */
    public function test_checking_prise_de_contact_ok_without_type_client_is_rejected(): void
    {
        config(['services.n8n.auth_webhook_url' => 'https://n8n.example.com/webhook/auth']);
        Http::fake();

        $admin = $this->actingAsAdmin();
        $contact = ContactMessage::create([
            'prenom' => 'Faille',
            'email' => 'faille@example.com',
            'sujet' => 'Autre question',
            'message' => 'Un message.',
            'prise_de_contact_ok' => false,
            'date_creation' => now(),
        ]);

        $response = $this->actingAs($admin)->patch("/admin/contacts/{$contact->id}", [
            'prise_de_contact_ok' => true,
        ]);

        $response->assertSessionHasErrors('type_client');
        $this->assertNull(Compte::where('email', 'faille@example.com')->first());
        Http::assertNothingSent();
    }

    public function test_does_not_recreate_account_if_contact_already_linked_to_a_client(): void
    {
        config(['services.n8n.auth_webhook_url' => 'https://n8n.example.com/webhook/auth']);
        Http::fake();

        $admin = $this->actingAsAdmin();
        $existingCompte = Compte::factory()->create(['email' => 'deja-client@example.com']);
        $existingClient = Client::factory()->for($existingCompte)->create();

        $contact = ContactMessage::create([
            'client_id' => $existingClient->id,
            'prenom' => 'Chloé',
            'email' => 'deja-client@example.com',
            'sujet' => 'Autre question',
            'message' => 'Un message.',
            'prise_de_contact_ok' => false,
            'date_creation' => now(),
        ]);

        $this->actingAs($admin)->patch("/admin/contacts/{$contact->id}", [
            'prise_de_contact_ok' => true,
            'type_client' => 'Entreprise',
        ]);

        $this->assertSame(1, Compte::where('email', 'deja-client@example.com')->count());
        $this->assertNull(
            $existingClient->fresh()->type_client,
            'Le rôle du client déjà lié ne doit pas être écrasé par le popup entreprise/particulier.'
        );
    }

    public function test_unchecking_prise_de_contact_ok_does_not_send_invite(): void
    {
        config(['services.n8n.auth_webhook_url' => 'https://n8n.example.com/webhook/auth']);
        Http::fake();

        $admin = $this->actingAsAdmin();
        $contact = ContactMessage::create([
            'prenom' => 'Dan',
            'email' => 'dan@example.com',
            'sujet' => 'Autre question',
            'message' => 'Un message.',
            'prise_de_contact_ok' => true,
            'date_creation' => now(),
        ]);

        $this->actingAs($admin)->patch("/admin/contacts/{$contact->id}", ['prise_de_contact_ok' => false]);

        Http::assertNothingSent();
    }

    public function test_request_review_sends_webhook_with_client_email(): void
    {
        config(['services.n8n.avis_webhook_url' => 'https://n8n.example.com/webhook/avis']);
        Http::fake();

        $admin = $this->actingAsAdmin();
        $compte = Compte::factory()->create(['email' => 'client@example.com']);
        $client = Client::factory()->for($compte)->create(['nom_complet' => 'Eve Client']);

        $response = $this->actingAs($admin)->post("/admin/comptes/{$client->id}/demander-avis");

        $response->assertRedirect();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://n8n.example.com/webhook/avis'
                && $request['event'] === 'review_requested'
                && $request['email'] === 'client@example.com'
                && $request['name'] === 'Eve Client'
                && $request['dashboard_url'] === route('dashboard');
        });
    }

    public function test_admin_can_delete_a_contact_message(): void
    {
        $admin = $this->actingAsAdmin();
        $contact = ContactMessage::create([
            'prenom' => 'Farid',
            'email' => 'farid@example.com',
            'sujet' => 'Autre question',
            'message' => 'Un message à supprimer.',
            'prise_de_contact_ok' => false,
            'date_creation' => now(),
        ]);

        $response = $this->actingAs($admin)->delete("/admin/contacts/{$contact->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('contact_siteweb', ['id' => $contact->id]);
    }
}

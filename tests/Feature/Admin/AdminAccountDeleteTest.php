<?php

namespace Tests\Feature\Admin;

use App\Models\AvisClient;
use App\Models\Client;
use App\Models\Compte;
use App\Models\ContactMessage;
use App\Models\Facture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Couvre AdminController::destroyAccount — bouton "Supprimer le compte" du
 * dashboard admin. La suppression cible "comptes" (source de vérité) : la
 * cascade en base fait le reste (voir migrations clients/prestations/factures
 * ->cascadeOnDelete, avis_clients/contact_siteweb ->nullOnDelete).
 */
class AdminAccountDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): Compte
    {
        $compte = Compte::factory()->create();
        Client::factory()->for($compte)->admin()->create();

        return $compte;
    }

    public function test_admin_can_delete_a_client_account(): void
    {
        Storage::fake('local');

        $admin = $this->actingAsAdmin();

        $targetCompte = Compte::factory()->create(['email' => 'client-a-supprimer@example.com']);
        $client = Client::factory()->for($targetCompte)->create();

        $prestation = $client->prestations()->create([
            'type_service' => 'Formation IA',
            'prix' => 500,
            'statut_facturation' => 'Payé',
        ]);
        $facture = Facture::factory()->for($client)->create();
        Storage::disk('local')->put($facture->chemin_fichier, 'contenu-pdf');

        $avis = AvisClient::create([
            'client_id' => $client->id,
            'prenom_nom' => $client->nom_complet,
            'satisfaction' => 5,
            'commentaire' => 'Top.',
            'created_at' => now(),
        ]);
        $contact = ContactMessage::create([
            'client_id' => $client->id,
            'prenom' => 'Client',
            'email' => 'client-a-supprimer@example.com',
            'sujet' => 'Autre question',
            'message' => 'Un message.',
            'prise_de_contact_ok' => true,
            'date_creation' => now(),
        ]);

        $response = $this->actingAs($admin)->delete("/admin/comptes/{$client->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('comptes', ['id' => $targetCompte->id]);
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $this->assertDatabaseMissing('prestations', ['id' => $prestation->id]);
        $this->assertDatabaseMissing('factures', ['id' => $facture->id]);
        Storage::disk('local')->assertMissing($facture->chemin_fichier);

        // Historique conservé, juste détaché du client supprimé.
        $this->assertDatabaseHas('avis_clients', ['id' => $avis->id, 'client_id' => null]);
        $this->assertDatabaseHas('contact_siteweb', ['id' => $contact->id, 'client_id' => null]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->actingAsAdmin();
        // Un deuxième admin pour ne pas confondre avec la règle "dernier admin".
        $otherAdminCompte = Compte::factory()->create();
        Client::factory()->for($otherAdminCompte)->admin()->create();

        $response = $this->actingAs($admin)->delete("/admin/comptes/{$admin->client->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('comptes', ['id' => $admin->id]);
    }

    public function test_cannot_delete_the_last_admin_account(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->actingAs($admin)->delete("/admin/comptes/{$admin->client->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('comptes', ['id' => $admin->id]);
    }
}

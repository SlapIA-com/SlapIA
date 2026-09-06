<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use App\Models\Compte;
use App\Models\Facture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Le point le plus sensible de tout le site : un client ne doit JAMAIS
 * pouvoir consulter la facture d'un autre client. DashboardController::
 * viewInvoice() récupère par INDEX dans SA PROPRE liste de factures
 * (jamais par ID global fourni dans l'URL) — ce test vérifie que ça reste
 * vrai après un futur refactor.
 */
class InvoiceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_cannot_access_an_invoice_index_beyond_their_own_list(): void
    {
        $compteA = Compte::factory()->create();
        $clientA = Client::factory()->for($compteA)->create();
        Facture::factory()->for($clientA)->create();

        $compteB = Compte::factory()->create();
        $clientB = Client::factory()->for($compteB)->create();
        Facture::factory()->for($clientB)->create();

        // Client B n'a qu'UNE facture à lui (index 0). Demander l'index 1
        // ne doit jamais renvoyer la facture de client A — juste un 404.
        $response = $this->actingAs($compteB)->get('/dashboard/factures/1');

        $response->assertNotFound();
    }

    public function test_client_can_access_their_own_invoice_by_index(): void
    {
        // Storage::fake évite d'écrire un vrai fichier sous storage/ (le
        // disque "local" de cette appli pointe sur storage_path() entier,
        // pas juste storage/app — voir config/filesystems.php).
        Storage::fake('local');

        $compte = Compte::factory()->create();
        $client = Client::factory()->for($compte)->create();
        $facture = Facture::factory()->for($client)->create([
            'chemin_fichier' => 'invoices/test/facture.pdf',
        ]);

        Storage::disk('local')->put($facture->chemin_fichier, '%PDF-1.4 contenu de test');

        $response = $this->actingAs($compte)->get('/dashboard/factures/0');

        $response->assertOk();
    }
}

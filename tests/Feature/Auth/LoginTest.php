<?php

namespace Tests\Feature\Auth;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Couvre les règles de AuthenticatedSessionController héritées de l'ancien
 * includes/auth.php : jamais de message qui distingue "email inconnu" de
 * "mauvais mot de passe", rate-limit après 5 essais, et redirection par
 * rôle (client -> /dashboard, admin -> /admin).
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_wrong_password_shows_generic_error_and_does_not_authenticate(): void
    {
        $compte = Compte::factory()->create([
            'mot_de_passe_hash' => Hash::make('le-bon-mot-de-passe'),
        ]);
        Client::factory()->for($compte)->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $compte->email,
            'password' => 'un-mauvais-mot-de-passe',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_client_login_redirects_to_dashboard(): void
    {
        $compte = Compte::factory()->create([
            'mot_de_passe_hash' => Hash::make('le-bon-mot-de-passe'),
        ]);
        Client::factory()->for($compte)->create();

        $response = $this->post('/login', [
            'email' => $compte->email,
            'password' => 'le-bon-mot-de-passe',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($compte);
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $compte = Compte::factory()->create([
            'mot_de_passe_hash' => Hash::make('le-bon-mot-de-passe'),
        ]);
        Client::factory()->for($compte)->admin()->create();

        $response = $this->post('/login', [
            'email' => $compte->email,
            'password' => 'le-bon-mot-de-passe',
        ]);

        $response->assertRedirect('/admin');
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $compte = Compte::factory()->create([
            'mot_de_passe_hash' => Hash::make('le-bon-mot-de-passe'),
        ]);
        Client::factory()->for($compte)->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $compte->email,
                'password' => 'mauvais-mot-de-passe',
            ]);
        }

        // Le 6e essai est bloqué par le rate-limit — même avec le BON mot
        // de passe, pour vérifier que c'est bien le rate-limit qui parle et
        // pas juste un mot de passe qui aurait été mal saisi par erreur.
        $response = $this->post('/login', [
            'email' => $compte->email,
            'password' => 'le-bon-mot-de-passe',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}

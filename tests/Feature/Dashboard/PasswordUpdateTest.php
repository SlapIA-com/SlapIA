<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Non-régression du 5 septembre 2026 : la clé
     * 'dashboard.err_wrong_current_password' était absente de
     * lang/fr/messages.php (présente seulement en EN/DE), donc un client
     * français qui se trompait de mot de passe actuel voyait la clé brute
     * "messages.dashboard.err_wrong_current_password" au lieu du message
     * "Mot de passe actuel incorrect." — le check-translations.php (voir
     * composer check-translations) est censé attraper ça en amont
     * désormais ; ce test vérifie le comportement utilisateur final.
     */
    public function test_wrong_current_password_shows_translated_french_message(): void
    {
        $compte = Compte::factory()->create([
            'mot_de_passe_hash' => Hash::make('le-bon-mot-de-passe'),
        ]);
        Client::factory()->for($compte)->create();

        $response = $this->actingAs($compte)->from('/dashboard')->post('/dashboard/password', [
            'current_password' => 'mauvais-mot-de-passe',
            'password' => 'un-nouveau-mot-de-passe',
            'password_confirmation' => 'un-nouveau-mot-de-passe',
        ]);

        $response->assertSessionHasErrors('current_password');

        $message = session('errors')->get('current_password')[0];

        $this->assertSame('Mot de passe actuel incorrect.', $message);
        $this->assertStringNotContainsString('err_wrong_current_password', $message);
    }
}

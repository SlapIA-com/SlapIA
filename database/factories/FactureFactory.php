<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Facture;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Facture> */
class FactureFactory extends Factory
{
    protected $model = Facture::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'nom_fichier' => 'facture-'.fake()->numerify('####').'.pdf',
            'chemin_fichier' => 'invoices/'.fake()->numerify('####').'/facture.pdf',
            'mime_type' => 'application/pdf',
            'taille_octets' => 12345,
        ];
    }
}

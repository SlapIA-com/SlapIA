<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Client> */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'compte_id' => Compte::factory(),
            'nom_complet' => fake()->name(),
            'type_client' => 'Particulier',
        ];
    }

    public function admin(): static
    {
        return $this->state(['type_client' => null]);
    }

    public function entreprise(): static
    {
        return $this->state(['type_client' => 'Entreprise']);
    }
}

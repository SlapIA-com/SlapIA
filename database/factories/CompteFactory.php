<?php

namespace Database\Factories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<Compte> */
class CompteFactory extends Factory
{
    protected $model = Compte::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'mot_de_passe_hash' => Hash::make('password'),
            'mail_avis' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str; // Pour la fonction slug

class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true); // Génère 2 mots uniques pour le nom
        return [
            'name' => $name,
            'slug' => Str::slug($name), // Crée un slug à partir du nom
            'description' => $this->faker->sentence(),
            'icon' => null, // Tu peux ajouter des chemins d'icônes si tu en as
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Crée un utilisateur si aucun n'est fourni
            'category_id' => Category::factory(), // Crée une catégorie si aucune n'est fournie
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 5, 500), // Prix entre 5 et 500
            'price_type' => $this->faker->randomElement(['fixed', 'hourly', 'negotiable']),
            'location' => $this->faker->city(), // Nom de ville factice (tu peux le personnaliser pour la RDC plus tard)
            'contact_phone' => $this->faker->phoneNumber(),
            'contact_email' => $this->faker->unique()->safeEmail(),
            'is_featured' => $this->faker->boolean(20), // 20% de chance d'être "featured"
            'expires_at' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'status' => $this->faker->randomElement(['active', 'pending', 'completed']),
        ];
    }
}

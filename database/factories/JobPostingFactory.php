<?php

namespace Database\Factories;

// N'oublie pas d'importer le modèle renommé !
use App\Models\JobPosting; // <-- CHANGEMENT ICI : Importe le nouveau modèle JobPosting
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

// Renomme la classe de JobFactory à JobPostingFactory
class JobPostingFactory extends Factory // <-- CHANGEMENT ICI : Renomme la classe
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = JobPosting::class; // <-- CHANGEMENT ICI : Fais pointer vers le nouveau modèle

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Assign an existing user, or create one if no users exist.
            // It's better to ensure users are seeded before jobs.
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),

            // Assign an existing category. This is the key change to prevent duplicates.
            // We ensure categories are seeded before jobs.
            'category_id' => Category::inRandomOrder()->first()->id ?? Category::factory(),

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
<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\JobPosting; // <-- CHANGEMENT ICI : Importe le nouveau modèle JobPosting
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Assigner un utilisateur existant de manière aléatoire.
        // Si aucun utilisateur n'existe (ce qui ne devrait pas arriver si DatabaseSeeder s'exécute en premier),
        // alors créez-en un.
        $userId = User::inRandomOrder()->first()->id ?? User::factory();

        // Assigner un job existant de manière aléatoire, ou null 30% du temps.
        // Si aucun job n'existe (ce qui ne devrait pas arriver si JobSeeder s'exécute en premier),
        // alors créez-en un, sinon assignez null.
        // CHANGEMENT ICI : Utilise JobPosting::class et JobPosting::factory()
        $jobId = $this->faker->boolean(70) ? (JobPosting::inRandomOrder()->first()->id ?? JobPosting::factory()) : null;

        return [
            'user_id' => $userId,
            'job_id' => $jobId,
            'transaction_id' => $this->faker->unique()->uuid(),
            'amount' => $this->faker->randomFloat(2, 1, 100),
            'currency' => $this->faker->randomElement(['CDF', 'USD']), // Monnaies locales
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'payment_method' => $this->faker->randomElement(['Orange Money', 'M-Pesa', 'Airtel Money']), // Méthodes de paiement locales
            'description' => $this->faker->sentence(),
        ];
    }
}
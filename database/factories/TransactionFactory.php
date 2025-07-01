<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Job;
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
        return [
            'user_id' => User::factory(),
            'job_id' => $this->faker->boolean(70) ? Job::factory() : null, // 70% de chance d'être lié à un job
            'transaction_id' => $this->faker->unique()->uuid(),
            'amount' => $this->faker->randomFloat(2, 1, 100),
            'currency' => $this->faker->randomElement(['CDF', 'USD']), // Monnaies locales
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'payment_method' => $this->faker->randomElement(['Orange Money', 'M-Pesa', 'Airtel Money']), // Méthodes de paiement locales
            'description' => $this->faker->sentence(),
        ];
    }
}

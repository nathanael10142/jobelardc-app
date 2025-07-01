<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User; // Assurez-vous que le modèle User est importé

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone_number' => $this->faker->phoneNumber(), // Ajouté
            'bio' => $this->faker->paragraph(2), // Ajouté, 2 phrases de paragraphe
            'profile_picture' => null, // Ajouté, par défaut null ou une URL factice si vous avez un service d'images
            'location' => $this->faker->city(), // Ajouté
            // user_type doit être une des valeurs valides de votre contrainte CHECK
            'user_type' => $this->faker->randomElement(['prestataire', 'demandeur', 'both', 'employer', 'candidate']), // Ajouté
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

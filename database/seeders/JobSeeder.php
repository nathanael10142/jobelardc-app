<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assurez-vous qu'il y a des utilisateurs et des catégories avant de créer des jobs
        if (User::count() == 0) {
            User::factory(10)->create();
        }
        if (Category::count() == 0) {
            Category::factory(10)->create(); // Crée quelques catégories si elles n'existent pas
        }

        // Crée 50 jobs factices
        Job::factory(50)->create();
    }
}

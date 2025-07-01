<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assurez-vous qu'il y a des utilisateurs avant de créer des transactions
        if (User::count() == 0) {
            User::factory(10)->create();
        }

        // Crée 30 transactions factices
        Transaction::factory(30)->create();
    }
}

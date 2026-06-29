<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. On crée d'abord un compte Administrateur de test fixe pour pouvoir te connecter plus tard
        $admin = User::create([
            'name' => 'Kouassi Antoine',
            'email' => 'admin@cardeal.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // Le mot de passe sera 'password'
            'remember_token' => Str::random(10),
        ]);

        // 2. On crée un deuxième agent de test pour simuler le cas "Multi-agents" (Option B)
        $agent = User::create([
            'name' => 'Karim Ouedraogo',
            'email' => 'karim@cardeal.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ]);

        // 3. On génère 10 véhicules pour l'admin principal
        Vehicle::factory(10)->create([
            'user_id' => $admin->id
        ]);

        // 4. On génère 5 véhicules pour l'autre agent
        Vehicle::factory(5)->create([
            'user_id' => $agent->id
        ]);
    }
}
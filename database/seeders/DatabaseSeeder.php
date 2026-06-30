<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
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

        // 3. On génère 10 véhicules pour l'admin principal ET on leur attache des images
        Vehicle::factory(10)->create([
            'user_id' => $admin->id
        ])->each(function ($vehicle) {
            // Pour chaque véhicule créé, on génère 3 images
            // La première sera l'image principale (is_main = true)
            $vehicle->images()->create([
                'path' => 'vehicles/main_car_' . rand(1, 5) . '.jpg',
                'is_main' => true
            ]);

            // Les deux autres seront des images secondaires de la galerie
            VehicleImage::factory(2)->create([
                'vehicle_id' => $vehicle->id,
                'is_main' => false
            ]);
        });

        // 4. On fait exactement pareil pour les 5 véhicules de l'autre agent
        Vehicle::factory(5)->create([
            'user_id' => $agent->id
        ])->each(function ($vehicle) {
            $vehicle->images()->create([
                'path' => 'vehicles/main_car_' . rand(1, 5) . '.jpg',
                'is_main' => true
            ]);

            VehicleImage::factory(2)->create([
                'vehicle_id' => $vehicle->id,
                'is_main' => false
            ]);
        });
    }
}

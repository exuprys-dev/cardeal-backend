<?php

namespace Database\Factories;

use App\Models\VehicleImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleImage>
 */
class VehicleImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = VehicleImage::class;

    public function definition(): array
    {
        return [
            // On simule un chemin de stockage local propre
            'path' => 'vehicles/car_' . $this->faker->numberBetween(1, 10) . '.jpg',
            'is_main' => false, // Par défaut, ce n'est pas l'image principale
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brands = ['Toyota', 'Peugeot', 'Mercedes-Benz', 'Hyundai', 'Kia', 'Nissan'];
        $models = [
            'Toyota' => ['Corolla', 'Camry', 'RAV4', 'Hilux'],
            'Peugeot' => ['308', '2008', '508', '4008'],
            'Mercedes-Benz' => ['Classe C', 'Classe E', 'GLC', 'GLE'],
            'Hyundai' => ['Elantra', 'Tucson', 'Santa Fe', 'i30'],
            'Kia' => ['Sportage', 'Rio', 'Sorento', 'Picanto'],
            'Nissan' => ['Navara', 'Sunny', 'Qashqai', 'X-Trail']
        ];

        // Choisit une marque au hasard directement depuis les clés du tableau $models
        $brand = $this->faker->randomElement(array_keys($models));
        // Choisit un modèle correspondant
        $model = $this->faker->randomElement($models[$brand]);

        return [
            'title' => $brand . ' ' . $model . ' ' . $this->faker->randomElement(['Édition Limitée', 'Luxe', 'Sport', 'Standard']),
            'brand' => $brand,
            'model' => $model,
            'year' => $this->faker->numberBetween(2015, 2026),
            'mileage' => $this->faker->numberBetween(0, 180000),
            'price' => $this->faker->randomElement([4500000, 6000000, 8500000, 12000000, 25000000]), // Prix en FCFA par exemple
            'fuel_type' => $this->faker->randomElement(['Essence', 'Diesel', 'Hybride']),
            'transmission' => $this->faker->randomElement(['Manuelle', 'Automatique']),
            'condition' => $this->faker->randomElement(['Neuf', 'Occasion']),
            'status' => 'Disponible',
            'description' => $this->faker->paragraph(3),
            'color' => $this->faker->safeColorName(),
            'engine' => $this->faker->randomElement(['1.6L', '2.0L', 'V6 3.5L']),
            'doors' => $this->faker->randomElement([4, 5]),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => 'Salle ' . fake()->unique()->bothify('??-###'),
            'etage' => fake()->numberBetween(0, 5),
            'capacite' => fake()->randomElement([4, 6, 8, 10, 12, 15, 20, 25, 30]),
            'equipement' => fake()->randomElements(
                ['Projecteur', 'Tableau blanc', 'Écran', 'Visioconférence', 'WiFi', 'Téléphone'],
                fake()->numberBetween(2, 5)
            ),
        ];
    }
}

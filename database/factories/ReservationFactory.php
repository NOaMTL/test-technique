<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('now', '+30 days');
        
        // Générer une heure de début en multiples de 30 minutes (08:00 - 17:00)
        $heureIndex = fake()->numberBetween(16, 33); // 16 = 08:00, 33 = 17:00 (réduit pour éviter dépassement)
        $heureDebut = sprintf('%02d:%02d', intdiv($heureIndex, 2) + 8, ($heureIndex % 2) * 30);
        
        // Générer une heure de fin 1 à 4 créneaux (30min) plus tard
        $dureeCreneaux = fake()->numberBetween(1, 4);
        $heureFinIndex = $heureIndex + $dureeCreneaux;
        
        // S'assurer que l'heure de fin ne dépasse pas 19:30 (index 39)
        if ($heureFinIndex > 39) {
            $heureFinIndex = 39;
        }
        
        $heureFin = sprintf('%02d:%02d', 
            intdiv($heureFinIndex, 2) + 8, 
            ($heureFinIndex % 2) * 30
        );

        return [
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'titre' => fake()->randomElement([
                'Réunion d\'équipe',
                'Présentation client',
                'Formation',
                'Workshop',
                'Brainstorming',
                'Réunion de projet',
                'Entretien',
            ]) . ' - ' . fake()->company(),
            'date' => $date->format('Y-m-d'),
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
            'description' => fake()->optional(0.7)->paragraph(),
            'nombre_personnes' => fake()->numberBetween(2, 10),
        ];
    }

    /**
     * Indicate that the reservation is in the past.
     */
    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $date = fake()->dateTimeBetween('-30 days', '-1 day');
            
            // Horaires en multiples de 30 minutes
            $heureIndex = fake()->numberBetween(16, 30);
            $heureDebut = sprintf('%02d:%02d', intdiv($heureIndex, 2) + 8, ($heureIndex % 2) * 30);
            $dureeCreneaux = fake()->numberBetween(1, 4);
            $heureFin = sprintf('%02d:%02d', 
                intdiv($heureIndex + $dureeCreneaux, 2) + 8, 
                (($heureIndex + $dureeCreneaux) % 2) * 30
            );
            
            return [
                'date' => $date->format('Y-m-d'),
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin,
            ];
        });
    }

    /**
     * Indicate that the reservation is currently active.
     */
    public function current(): static
    {
        return $this->state(function (array $attributes) {
            $now = now();
            // Arrondir à la demi-heure précédente
            $minute = $now->minute < 30 ? 0 : 30;
            $heure = $now->minute < 30 ? $now->hour - 1 : $now->hour;
            
            return [
                'date' => $now->format('Y-m-d'),
                'heure_debut' => sprintf('%02d:%02d', $heure, $minute),
                'heure_fin' => sprintf('%02d:%02d', $heure + 2, $minute),
            ];
        });
    }
}

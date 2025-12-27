<?php

namespace Tests\Feature\Api;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to format time value to HH:MM format
     */
    private function formatTime($time): string
    {
        if (empty($time)) {
            throw new \RuntimeException('Time value is empty');
        }
        
        // Convertir en string si ce n'est pas déjà le cas
        $time = (string) $time;
        
        // Si c'est déjà au format HH:MM
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        // Si c'est au format HH:MM:SS, extraire HH:MM
        if (preg_match('/^(\d{2}:\d{2}):\d{2}$/', $time, $matches)) {
            return $matches[1];
        }
        // Sinon, essayer substr comme fallback
        if (strlen($time) >= 5) {
            return substr($time, 0, 5);
        }
        
        throw new \RuntimeException("Invalid time format: {$time}");
    }

    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/reservations/user');
        $response->assertStatus(401);

        $response = $this->getJson('/api/rooms');
        $response->assertStatus(401);
    }

    public function test_user_can_list_their_reservations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $room = Room::factory()->create();

        // Réservations de l'utilisateur
        Reservation::factory()->count(3)->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);

        // Réservation d'un autre utilisateur
        Reservation::factory()->create([
            'user_id' => $otherUser->id,
            'room_id' => $room->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/reservations/user');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_reservation(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $date = now()->addDays(2);

        $reservationData = [
            'room_id' => $room->id,
            'titre' => 'Réunion test',
            'date' => $date->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'description' => 'Description de test',
            'nombre_personnes' => 5,
            'participants' => [], // Optionnel
        ];

        $response = $this->actingAs($user)->postJson('/api/reservations', $reservationData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'room_id' => $room->id,
            'titre' => 'Réunion test',
        ]);
    }

    public function test_cannot_create_reservation_in_the_past(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $reservationData = [
            'room_id' => $room->id,
            'titre' => 'Réunion test',
            'date' => now()->subDay()->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ];

        $response = $this->actingAs($user)->postJson('/api/reservations', $reservationData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_cannot_create_overlapping_reservation(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
$date = now()->addDays(2)->format('Y-m-d');

        // Créer une réservation existante
        Reservation::factory()->create([
            'room_id' => $room->id,
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        // Essayer de créer une réservation qui chevauche
        $reservationData = [
            'room_id' => $room->id,
            'titre' => 'Réunion test',
            'date' => $date,
            'heure_debut' => '10:30',
            'heure_fin' => '11:30',
        ];

        $response = $this->actingAs($user)->postJson('/api/reservations', $reservationData);

        $response->assertStatus(422);
    }

    public function test_user_can_update_their_reservation(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);

        // Forcer le rechargement pour avoir les valeurs fraîches de la BDD
        $reservation->refresh();

        $updateData = [
            'room_id' => $room->id,
            'titre' => 'Titre modifié',
            'description' => 'Description modifiée',
            'date' => now()->addDays(3)->format('Y-m-d'), // Date valide dans le futur
            'heure_debut' => '14:00', // Heure valide statique
            'heure_fin' => '15:30', // Heure valide statique
        ];

        $response = $this->actingAs($user)->putJson("/api/reservations/{$reservation->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'titre' => 'Titre modifié',
        ]);
    }

    public function test_user_cannot_update_another_users_reservation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $room = Room::factory()->create();
        
        $reservation = Reservation::factory()->create([
            'user_id' => $otherUser->id,
            'room_id' => $room->id,
        ]);

        // Forcer le rechargement pour avoir les valeurs fraîches de la BDD
        $reservation->refresh();

        $updateData = [
            'room_id' => $room->id,
            'titre' => 'Titre modifié',
            'date' => now()->addDays(3)->format('Y-m-d'), // Date valide dans le futur
            'heure_debut' => '10:00', // Heure valide statique
            'heure_fin' => '11:30', // Heure valide statique
        ];

        $response = $this->actingAs($user)->putJson("/api/reservations/{$reservation->id}", $updateData);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_reservation(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/reservations/{$reservation->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('reservations', [
            'id' => $reservation->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_reservation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $room = Room::factory()->create();
        
        $reservation = Reservation::factory()->create([
            'user_id' => $otherUser->id,
            'room_id' => $room->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/reservations/{$reservation->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_see_all_reservations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $room = Room::factory()->create();

        Reservation::factory()->count(2)->create(['user_id' => $user1->id, 'room_id' => $room->id]);
        Reservation::factory()->count(3)->create(['user_id' => $user2->id, 'room_id' => $room->id]);

        $response = $this->actingAs($admin)->getJson('/api/reservations');

        $response->assertStatus(200);
    }

    public function test_can_filter_reservations_by_room(): void
    {
        $user = User::factory()->create();
        $room1 = Room::factory()->create();
        $room2 = Room::factory()->create();

        Reservation::factory()->count(2)->create(['user_id' => $user->id, 'room_id' => $room1->id]);
        Reservation::factory()->count(3)->create(['user_id' => $user->id, 'room_id' => $room2->id]);

        $response = $this->actingAs($user)->getJson("/api/reservations/user?room_ids[]={$room1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_reservations_by_date_range(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $date1 = now()->addDays(5)->format('Y-m-d');
        $date2 = now()->addDays(15)->format('Y-m-d');

        // Réservations dans la période
        Reservation::factory()->count(2)->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'date' => $date1,
        ]);

        // Réservation hors période
        Reservation::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'date' => $date2,
        ]);

        $response = $this->actingAs($user)->getJson(
            '/api/reservations/user?date_start=' . now()->addDays(3)->toDateString() . 
            '&date_end=' . now()->addDays(10)->toDateString()
        );

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_reservations(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        Reservation::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'titre' => 'Réunion budget 2025',
        ]);

        Reservation::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'titre' => 'Formation développeurs',
        ]);

        $response = $this->actingAs($user)->getJson('/api/reservations/user?search=budget');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_pagination_works_correctly(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        Reservation::factory()->count(25)->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/reservations/user?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('total', 25)
            ->assertJsonPath('last_page', 3);
    }
}

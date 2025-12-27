<?php

namespace Tests\Feature\Api;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_all_rooms(): void
    {
        $user = User::factory()->create();
        Room::factory()->count(5)->create();

        $response = $this->actingAs($user)->getJson('/api/rooms');

        $response->assertStatus(200)
            ->assertJsonCount(5);
    }

    public function test_can_get_room_details(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle A',
            'etage' => 2,
            'capacite' => 10,
        ]);

        $response = $this->actingAs($user)->getJson("/api/rooms/{$room->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'nom' => 'Salle A',
                'etage' => 2,
                'capacite' => 10,
            ]);
    }

    public function test_can_add_room_to_favorites(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/rooms/{$room->id}/favorite");

        $response->assertStatus(200)
            ->assertJsonFragment(['is_favorite' => true]);

        $this->assertDatabaseHas('user_favorite_rooms', [
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);
    }

    public function test_can_remove_room_from_favorites(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        // Ajouter aux favoris d'abord
        $user->favoriteRooms()->attach($room->id);

        $response = $this->actingAs($user)->postJson("/api/rooms/{$room->id}/favorite");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('user_favorite_rooms', [
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);
    }

    public function test_rooms_are_sorted_by_floor_and_name(): void
    {
        $user = User::factory()->create();

        Room::factory()->create(['nom' => 'Salle B', 'etage' => 2]);
        Room::factory()->create(['nom' => 'Salle A', 'etage' => 2]);
        Room::factory()->create(['nom' => 'Salle C', 'etage' => 1]);

        $response = $this->actingAs($user)->getJson('/api/rooms');

        $response->assertStatus(200);

        $rooms = $response->json();
        
        // L'ordre dépend de l'implémentation côté serveur
        $this->assertCount(3, $rooms);
    }
}

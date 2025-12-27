<?php

namespace Tests\Feature\Api;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests pour vérifier que la liste des salles disponibles
 * correspond aux critères de l'utilisateur lors d'une réservation
 */
class RoomAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Récupérer toutes les salles disponibles sans filtres
     */
    public function test_can_get_all_available_rooms_without_conflicts(): void
    {
        $user = User::factory()->create();
        
        // Créer 3 salles
        Room::factory()->count(3)->create();

        $response = $this->actingAs($user)->postJson('/api/rooms/available', [
            'date' => now()->addDays(1)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /**
     * Test : Exclure les salles déjà réservées (conflit de créneau)
     */
    public function test_excludes_rooms_with_time_conflicts(): void
    {
        $user = User::factory()->create();
        
        $room1 = Room::factory()->create(['nom' => 'Salle A']);
        $room2 = Room::factory()->create(['nom' => 'Salle B']);
        $room3 = Room::factory()->create(['nom' => 'Salle C']);

        $date = now()->addDays(2)->format('Y-m-d');

        // Réserver la Salle A de 10h à 11h
        Reservation::factory()->create([
            'room_id' => $room1->id,
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        // Chercher une salle de 10h30 à 11h30 (chevauche la réservation de A)
        $response = $this->actingAs($user)->postJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '10:30',
            'heure_fin' => '11:30',
        ]);

        $response->assertStatus(200);
        
        $rooms = $response->json();
        
        // Doit retourner seulement B et C (pas A car conflit)
        $this->assertCount(2, $rooms);
        $this->assertNotContains('Salle A', array_column($rooms, 'nom'));
        $this->assertContains('Salle B', array_column($rooms, 'nom'));
        $this->assertContains('Salle C', array_column($rooms, 'nom'));
    }

    /**
     * Test : Filtrer par capacité minimale
     */
    public function test_filters_rooms_by_minimum_capacity(): void
    {
        $user = User::factory()->create();
        
        Room::factory()->create(['nom' => 'Petite salle', 'capacite' => 5]);
        Room::factory()->create(['nom' => 'Moyenne salle', 'capacite' => 10]);
        Room::factory()->create(['nom' => 'Grande salle', 'capacite' => 20]);

        // Chercher des salles avec capacité minimale de 10
        $response = $this->actingAs($user)->postJson('/api/rooms/available', [
            'date' => now()->addDays(1)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'capacite_min' => 10,
        ]);

        $response->assertStatus(200);
        
        $rooms = $response->json();
        
        // Doit retourner seulement les salles >= 10 personnes
        $this->assertCount(2, $rooms);
        $this->assertNotContains('Petite salle', array_column($rooms, 'nom'));
        $this->assertContains('Moyenne salle', array_column($rooms, 'nom'));
        $this->assertContains('Grande salle', array_column($rooms, 'nom'));
    }

    /**
     * Test : Filtrer par équipements requis
     */
    public function test_filters_rooms_by_required_equipment(): void
    {
        $user = User::factory()->create();
        
        Room::factory()->create([
            'nom' => 'Salle A',
            'equipement' => ['Projecteur'],
        ]);
        
        Room::factory()->create([
            'nom' => 'Salle B',
            'equipement' => ['Projecteur', 'Vidéoconférence'],
        ]);
        
        Room::factory()->create([
            'nom' => 'Salle C',
            'equipement' => ['Tableau blanc'],
        ]);

        // Chercher des salles avec projecteur ET vidéoconférence
        $response = $this->actingAs($user)->postJson('/api/rooms/available', [
            'date' => now()->addDays(1)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'equipements' => ['Projecteur', 'Vidéoconférence'],
        ]);

        $response->assertStatus(200);
        
        $rooms = $response->json();
        
        // Doit retourner seulement la Salle B
        $this->assertCount(1, $rooms);
        $this->assertEquals('Salle B', $rooms[0]['nom']);
    }

    /**
     * Test : Exclure une réservation lors de la modification
     */
    public function test_can_exclude_reservation_when_editing(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        
        $date = now()->addDays(2)->format('Y-m-d');

        // Créer une réservation existante
        $existingReservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        // Chercher la disponibilité en excluant cette réservation (cas de modification)
        $response = $this->actingAs($user)->postJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'exclude_reservation_id' => $existingReservation->id,
        ]);

        $response->assertStatus(200);
        
        $rooms = $response->json();
        
        // Doit retourner la salle car on exclut la réservation existante
        $this->assertCount(1, $rooms);
        $this->assertEquals($room->id, $rooms[0]['id']);
    }

    /**
     * Test : Validation des contraintes de temps
     */
    public function test_validates_time_constraints(): void
    {
        $user = User::factory()->create();
        Room::factory()->create();

        // Heure de fin avant heure de début
        $response = $this->actingAs($user)->postJson('/api/rooms/available', [
            'date' => now()->addDays(1)->format('Y-m-d'),
            'heure_debut' => '11:00',
            'heure_fin' => '10:00', // Invalide
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['heure_fin']);
    }

    /**
     * Test : Les salles inactives ne sont pas retournées
     */
    public function test_excludes_inactive_rooms(): void
    {
        $user = User::factory()->create();
        
        Room::factory()->create(['nom' => 'Salle Active', 'is_active' => true]);
        Room::factory()->create(['nom' => 'Salle Inactive', 'is_active' => false]);

        $response = $this->actingAs($user)->postJson('/api/rooms/available', [
            'date' => now()->addDays(1)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(200);
        
        $rooms = $response->json();
        
        // Doit retourner seulement la salle active
        $this->assertCount(1, $rooms);
        $this->assertEquals('Salle Active', $rooms[0]['nom']);
    }

    /**
     * Test : Scénario complet - utilisateur crée une réservation
     */
    public function test_complete_user_reservation_flow(): void
    {
        $user = User::factory()->create();
        
        // Créer des salles avec différentes caractéristiques
        $petiteSalle = Room::factory()->create([
            'nom' => 'Salle Réunion A',
            'capacite' => 4,
            'equipement' => ['Tableau blanc'],
        ]);
        
        $grandeSalle = Room::factory()->create([
            'nom' => 'Salle Conférence B',
            'capacite' => 20,
            'equipement' => ['Projecteur', 'Vidéoconférence'],
        ]);
        
        $salleTech = Room::factory()->create([
            'nom' => 'Salle Tech C',
            'capacite' => 12,
            'equipement' => ['Projecteur', 'Vidéoconférence', 'Écrans multiples'],
        ]);

        $date = now()->addDays(3)->format('Y-m-d');

        // Réserver la grande salle de 14h à 15h
        Reservation::factory()->create([
            'room_id' => $grandeSalle->id,
            'date' => $date,
            'heure_debut' => '14:00',
            'heure_fin' => '15:00',
        ]);

        // L'utilisateur veut réserver pour :
        // - 8 personnes
        // - Avec projecteur et vidéoconférence
        // - De 14h à 15h (même créneau que la grande salle déjà prise)
        $response = $this->actingAs($user)->postJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '14:00',
            'heure_fin' => '15:00',
            'capacite_min' => 8,
            'equipements' => ['Projecteur', 'Vidéoconférence'],
        ]);

        $response->assertStatus(200);
        
        $rooms = $response->json();
        
        // Vérifications :
        // - Petite salle exclue (capacité insuffisante)
        // - Grande salle exclue (déjà réservée à ce créneau)
        // - Salle Tech disponible (capacité OK, équipements OK, pas de conflit)
        $this->assertCount(1, $rooms);
        $this->assertEquals('Salle Tech C', $rooms[0]['nom']);
        $this->assertEquals($salleTech->id, $rooms[0]['id']);
        $this->assertTrue($rooms[0]['is_available']);
    }

    /**
     * Test : Validation de la date (pas dans le passé pour les utilisateurs normaux)
     */
    public function test_normal_user_cannot_check_availability_for_past_dates(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Room::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/rooms/available', [
            'date' => now()->subDays(1)->format('Y-m-d'), // Date passée
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /**
     * Test : Les admins peuvent consulter les dates passées
     */
    public function test_admin_can_check_availability_for_past_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Room::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/rooms/available', [
            'date' => now()->subDays(1)->format('Y-m-d'), // Date passée
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test : Les salles favorites sont marquées correctement
     */
    public function test_marks_favorite_rooms_in_available_list(): void
    {
        $user = User::factory()->create();
        
        $room1 = Room::factory()->create(['nom' => 'Salle A']);
        $room2 = Room::factory()->create(['nom' => 'Salle B']);

        // Marquer room1 comme favori
        $user->favoriteRooms()->attach($room1->id);

        $response = $this->actingAs($user)->postJson('/api/rooms/available', [
            'date' => now()->addDays(1)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(200);
        
        $rooms = $response->json();
        
        $this->assertCount(2, $rooms);
        
        // Trouver les salles
        $roomAData = collect($rooms)->firstWhere('nom', 'Salle A');
        $roomBData = collect($rooms)->firstWhere('nom', 'Salle B');
        
        // Vérifier que room1 est marquée comme favorite
        $this->assertTrue($roomAData['is_favorite']);
        $this->assertFalse($roomBData['is_favorite']);
    }

    /**
     * Test : Chevauchement partiel - début pendant une réservation existante
     */
    public function test_detects_partial_overlap_at_start(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        
        $date = now()->addDays(2)->format('Y-m-d');

        // Réservation existante : 10h00 - 12h00
        Reservation::factory()->create([
            'room_id' => $room->id,
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ]);

        // Tenter de réserver : 11h00 - 13h00 (chevauche la fin de l'existante)
        $response = $this->actingAs($user)->postJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '11:00',
            'heure_fin' => '13:00',
        ]);

        $response->assertStatus(200);
        
        $rooms = $response->json();
        
        // La salle ne doit PAS être disponible
        $this->assertCount(0, $rooms);
    }

    /**
     * Test : Pas de chevauchement - créneaux consécutifs
     */
    public function test_allows_consecutive_time_slots(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        
        $date = now()->addDays(2)->format('Y-m-d');

        // Réservation existante : 10h00 - 11h00
        Reservation::factory()->create([
            'room_id' => $room->id,
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        // Réserver juste après : 11h00 - 12h00 (pas de chevauchement)
        $response = $this->actingAs($user)->postJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '11:00',
            'heure_fin' => '12:00',
        ]);

        $response->assertStatus(200);
        
        $rooms = $response->json();
        
        // La salle DOIT être disponible (créneaux consécutifs sans chevauchement)
        $this->assertCount(1, $rooms);
        $this->assertEquals($room->id, $rooms[0]['id']);
    }
}

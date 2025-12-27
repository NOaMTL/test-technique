<?php

namespace Tests\Feature\Api;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests pour l'endpoint /api/rooms/available
 * 
 * Ces tests vérifient que la liste des salles disponibles retournée
 * correspond exactement aux choix de l'utilisateur lors d'une réservation.
 */
class AvailableRoomsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les settings par défaut
        Setting::factory()->create(['key' => 'reservations.block_weekends', 'value' => '0']);
        Setting::factory()->create(['key' => 'reservations.slot_duration', 'value' => '30']);
        Setting::factory()->create(['key' => 'reservations.min_advance_hours', 'value' => '2']);
        Setting::factory()->create(['key' => 'reservations.max_advance_days', 'value' => '90']);
    }

    // ===========================
    // TESTS: Filtrage par disponibilité horaire
    // ===========================

    public function test_returns_only_available_rooms_for_given_time_slot(): void
    {
        $user = User::factory()->create();
        
        $room1 = Room::factory()->create(['nom' => 'Salle Libre']);
        $room2 = Room::factory()->create(['nom' => 'Salle Occupée']);
        $room3 = Room::factory()->create(['nom' => 'Salle Libre 2']);

        $date = now()->addDays(2)->format('Y-m-d');

        // Réservation existante sur room2 de 10h à 11h
        Reservation::factory()->create([
            'room_id' => $room2->id,
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        // L'utilisateur cherche une salle de 10h à 11h
        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2) // Seulement 2 salles disponibles
            ->assertJsonFragment(['nom' => 'Salle Libre'])
            ->assertJsonFragment(['nom' => 'Salle Libre 2'])
            ->assertJsonMissing(['nom' => 'Salle Occupée']);
    }

    public function test_returns_room_if_reservation_does_not_overlap(): void
    {
        $user = User::factory()->create();
        
        $room = Room::factory()->create(['nom' => 'Salle Test']);
        $date = now()->addDays(2)->format('Y-m-d');

        // Réservation de 09h à 10h
        Reservation::factory()->create([
            'room_id' => $room->id,
            'date' => $date,
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
        ]);

        // Recherche de 10h à 11h (pas de chevauchement)
        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['nom' => 'Salle Test']);
    }

    public function test_excludes_room_if_reservation_overlaps_partially(): void
    {
        $user = User::factory()->create();
        
        $room = Room::factory()->create(['nom' => 'Salle Chevauchement']);
        $date = now()->addDays(2)->format('Y-m-d');

        // Réservation de 10h à 12h
        Reservation::factory()->create([
            'room_id' => $room->id,
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ]);

        // Recherche de 11h à 13h (chevauchement de 11h à 12h)
        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '11:00',
            'heure_fin' => '13:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonMissing(['nom' => 'Salle Chevauchement']);
    }

    // ===========================
    // TESTS: Filtrage par contraintes de période (time_period)
    // ===========================

    public function test_excludes_morning_only_rooms_when_searching_afternoon(): void
    {
        $user = User::factory()->create();
        
        $morningRoom = Room::factory()->create([
            'nom' => 'Salle Matin Uniquement',
            'constraints' => ['time_period' => 'morning'],
        ]);
        
        $normalRoom = Room::factory()->create([
            'nom' => 'Salle Sans Contrainte',
            'constraints' => null,
        ]);

        $date = now()->addDays(2)->format('Y-m-d');

        // Recherche pour l'après-midi (14h-15h)
        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '14:00',
            'heure_fin' => '15:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['nom' => 'Salle Sans Contrainte'])
            ->assertJsonMissing(['nom' => 'Salle Matin Uniquement']);
    }

    public function test_includes_morning_only_rooms_when_searching_morning(): void
    {
        $user = User::factory()->create();
        
        $morningRoom = Room::factory()->create([
            'nom' => 'Salle Matin Uniquement',
            'constraints' => ['time_period' => 'morning'],
        ]);

        $date = now()->addDays(2)->format('Y-m-d');

        // Recherche pour le matin (09h-10h)
        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['nom' => 'Salle Matin Uniquement']);
    }

    public function test_excludes_afternoon_only_rooms_when_searching_morning(): void
    {
        $user = User::factory()->create();
        
        $afternoonRoom = Room::factory()->create([
            'nom' => 'Salle Après-midi Uniquement',
            'constraints' => ['time_period' => 'afternoon'],
        ]);
        
        $normalRoom = Room::factory()->create([
            'nom' => 'Salle Sans Contrainte',
            'constraints' => null,
        ]);

        $date = now()->addDays(2)->format('Y-m-d');

        // Recherche pour le matin (09h-10h)
        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['nom' => 'Salle Sans Contrainte'])
            ->assertJsonMissing(['nom' => 'Salle Après-midi Uniquement']);
    }

    // ===========================
    // TESTS: Filtrage par jours autorisés (days_allowed)
    // ===========================

    public function test_excludes_rooms_not_available_on_selected_day(): void
    {
        $user = User::factory()->create();
        
        $weekdayRoom = Room::factory()->create([
            'nom' => 'Salle Semaine Uniquement',
            'constraints' => [
                'days_allowed' => [1, 2, 3, 4, 5], // Lundi à Vendredi
            ],
        ]);
        
        $normalRoom = Room::factory()->create([
            'nom' => 'Salle 7j/7',
            'constraints' => null,
        ]);

        // Trouver le prochain samedi
        $nextSaturday = now()->next('Saturday');

        // Recherche pour samedi
        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $nextSaturday->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['nom' => 'Salle 7j/7'])
            ->assertJsonMissing(['nom' => 'Salle Semaine Uniquement']);
    }

    public function test_includes_rooms_available_on_selected_day(): void
    {
        $user = User::factory()->create();
        
        $weekdayRoom = Room::factory()->create([
            'nom' => 'Salle Semaine Uniquement',
            'constraints' => [
                'days_allowed' => [1, 2, 3, 4, 5], // Lundi à Vendredi
            ],
        ]);

        // Trouver le prochain lundi
        $nextMonday = now()->next('Monday');

        // Recherche pour lundi
        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $nextMonday->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['nom' => 'Salle Semaine Uniquement']);
    }

    // ===========================
    // TESTS: Filtrage par réservation à l'avance (advance_booking_days)
    // ===========================

    public function test_excludes_rooms_when_booking_too_far_in_advance(): void
    {
        $user = User::factory()->create();
        
        $limitedRoom = Room::factory()->create([
            'nom' => 'Salle Réservation Limitée',
            'constraints' => [
                'advance_booking_days' => 7, // Max 7 jours à l'avance
            ],
        ]);
        
        $normalRoom = Room::factory()->create([
            'nom' => 'Salle Sans Limite',
            'constraints' => null,
        ]);

        // Recherche pour dans 10 jours
        $date = now()->addDays(10)->format('Y-m-d');

        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['nom' => 'Salle Sans Limite'])
            ->assertJsonMissing(['nom' => 'Salle Réservation Limitée']);
    }

    public function test_includes_rooms_when_booking_within_advance_limit(): void
    {
        $user = User::factory()->create();
        
        $limitedRoom = Room::factory()->create([
            'nom' => 'Salle Réservation Limitée',
            'constraints' => [
                'advance_booking_days' => 7, // Max 7 jours à l'avance
            ],
        ]);

        // Recherche pour dans 5 jours
        $date = now()->addDays(5)->format('Y-m-d');

        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['nom' => 'Salle Réservation Limitée']);
    }

    // ===========================
    // TESTS: Filtrage par capacité
    // ===========================

    public function test_excludes_rooms_with_insufficient_capacity(): void
    {
        $user = User::factory()->create();
        
        $smallRoom = Room::factory()->create([
            'nom' => 'Petite Salle',
            'capacite' => 5,
        ]);
        
        $largeRoom = Room::factory()->create([
            'nom' => 'Grande Salle',
            'capacite' => 20,
        ]);

        $date = now()->addDays(2)->format('Y-m-d');

        // Recherche pour 10 personnes
        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'nombre_personnes' => 10,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['nom' => 'Grande Salle'])
            ->assertJsonMissing(['nom' => 'Petite Salle']);
    }

    public function test_includes_rooms_with_exact_capacity(): void
    {
        $user = User::factory()->create();
        
        $room = Room::factory()->create([
            'nom' => 'Salle Capacité Exacte',
            'capacite' => 10,
        ]);

        $date = now()->addDays(2)->format('Y-m-d');

        // Recherche pour 10 personnes (capacité exacte)
        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $date,
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'nombre_personnes' => 10,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['nom' => 'Salle Capacité Exacte']);
    }

    // ===========================
    // TESTS: Scénarios complexes (contraintes multiples)
    // ===========================

    public function test_filters_rooms_with_multiple_constraints(): void
    {
        $user = User::factory()->create();
        
        // Salle avec contraintes multiples
        $restrictedRoom = Room::factory()->create([
            'nom' => 'Salle Très Restreinte',
            'capacite' => 10,
            'constraints' => [
                'time_period' => 'morning',
                'days_allowed' => [1, 2, 3], // Lundi, Mardi, Mercredi
                'advance_booking_days' => 7,
            ],
        ]);
        
        // Salle normale
        $normalRoom = Room::factory()->create([
            'nom' => 'Salle Flexible',
            'capacite' => 20,
            'constraints' => null,
        ]);

        $nextMonday = now()->next('Monday');

        // Scénario 1: Recherche valide pour la salle restreinte (lundi matin, 5 jours à l'avance)
        $response1 = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $nextMonday->format('Y-m-d'),
            'heure_debut' => '09:00',
            'heure_fin' => '11:00',
            'nombre_personnes' => 8,
        ]);

        $response1->assertStatus(200)
            ->assertJsonFragment(['nom' => 'Salle Très Restreinte'])
            ->assertJsonFragment(['nom' => 'Salle Flexible']);

        // Scénario 2: Recherche invalide pour la salle restreinte (après-midi)
        $response2 = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $nextMonday->format('Y-m-d'),
            'heure_debut' => '14:00',
            'heure_fin' => '16:00',
            'nombre_personnes' => 8,
        ]);

        $response2->assertStatus(200)
            ->assertJsonMissing(['nom' => 'Salle Très Restreinte'])
            ->assertJsonFragment(['nom' => 'Salle Flexible']);

        // Scénario 3: Recherche invalide (trop de participants pour la salle restreinte)
        $response3 = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => $nextMonday->format('Y-m-d'),
            'heure_debut' => '09:00',
            'heure_fin' => '11:00',
            'nombre_personnes' => 15,
        ]);

        $response3->assertStatus(200)
            ->assertJsonMissing(['nom' => 'Salle Très Restreinte'])
            ->assertJsonFragment(['nom' => 'Salle Flexible']);
    }

    // ===========================
    // TESTS: Validation des paramètres
    // ===========================

    public function test_requires_date_parameter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_requires_start_time_parameter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => now()->addDays(2)->format('Y-m-d'),
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['heure_debut']);
    }

    public function test_requires_end_time_parameter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/rooms/available', [
            'date' => now()->addDays(2)->format('Y-m-d'),
            'heure_debut' => '10:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['heure_fin']);
    }
}

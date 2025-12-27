<?php

namespace Tests\Feature\Api;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests pour les contraintes de salles (Room Constraints)
 * 
 * Ce fichier teste toutes les contraintes JSON définies dans le champ `constraints` des salles :
 * - time_period (morning/afternoon/full_day)
 * - days_allowed (jours autorisés)
 * - advance_booking_days (réservation à l'avance)
 * - weekly_hours_quota (quota horaire hebdomadaire)
 * - daily_booking_limit (limite de réservations quotidiennes)
 * - min_participants (nombre minimum de participants)
 */
class RoomConstraintTest extends TestCase
{
    use RefreshDatabase;

    // ===========================
    // TESTS: Contrainte time_period
    // ===========================

    public function test_cannot_book_morning_only_room_in_afternoon(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Matin',
            'constraints' => ['time_period' => 'morning'],
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Réunion après-midi',
            'date' => now()->addDays(2)->format('Y-m-d'),
            'heure_debut' => '14:00',
            'heure_fin' => '15:00',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['heure_debut']);
    }

    public function test_can_book_morning_only_room_in_morning(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Matin',
            'constraints' => ['time_period' => 'morning'],
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Réunion matin',
            'date' => now()->addDays(2)->format('Y-m-d'),
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
        ]);

        $response->assertStatus(201);
    }

    public function test_cannot_book_afternoon_only_room_in_morning(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Après-midi',
            'constraints' => ['time_period' => 'afternoon'],
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Réunion matin',
            'date' => now()->addDays(2)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['heure_debut']);
    }

    public function test_can_book_afternoon_only_room_in_afternoon(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Après-midi',
            'constraints' => ['time_period' => 'afternoon'],
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Réunion après-midi',
            'date' => now()->addDays(2)->format('Y-m-d'),
            'heure_debut' => '14:00',
            'heure_fin' => '15:00',
        ]);

        $response->assertStatus(201);
    }

    // ===========================
    // TESTS: Contrainte days_allowed
    // ===========================

    public function test_cannot_book_room_on_forbidden_day(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Lundi-Vendredi',
            'constraints' => ['days_allowed' => [1, 2, 3, 4, 5]],
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Réunion samedi',
            'date' => now()->next('Saturday')->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['heure_debut']);
    }

    public function test_can_book_room_on_allowed_day(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Lundi-Vendredi',
            'constraints' => ['days_allowed' => [1, 2, 3, 4, 5]],
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Réunion lundi',
            'date' => now()->next('Monday')->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(201);
    }

    // ===========================
    // TESTS: Contrainte advance_booking_days
    // ===========================

    public function test_cannot_book_room_too_far_in_advance(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Avance Limitée',
            'constraints' => ['advance_booking_days' => 7],
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Réunion trop loin',
            'date' => now()->addDays(10)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['heure_debut']);
    }

    public function test_can_book_room_within_advance_booking_limit(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Avance Limitée',
            'constraints' => ['advance_booking_days' => 7],
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Réunion dans les délais',
            'date' => now()->addDays(5)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
        ]);

        $response->assertStatus(201);
    }

    // ===========================
    // TESTS: Contrainte weekly_hours_quota
    // ===========================

    public function test_cannot_exceed_weekly_hours_quota(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Quota Hebdo',
            'constraints' => ['weekly_hours_quota' => 5],
        ]);

        $nextMonday = now()->next('Monday');

        Reservation::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'date' => $nextMonday->format('Y-m-d'),
            'heure_debut' => '09:00',
            'heure_fin' => '13:00',
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Dépassement quota',
            'date' => $nextMonday->addDays(1)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['heure_debut']);
    }

    public function test_can_book_within_weekly_hours_quota(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Quota Hebdo',
            'constraints' => ['weekly_hours_quota' => 10],
        ]);

        $nextMonday = now()->next('Monday');

        Reservation::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'date' => $nextMonday->format('Y-m-d'),
            'heure_debut' => '09:00',
            'heure_fin' => '13:00',
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Dans le quota',
            'date' => $nextMonday->addDays(1)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '13:00',
        ]);

        $response->assertStatus(201);
    }

    // ===========================
    // TESTS: Contrainte daily_booking_limit
    // ===========================

    public function test_cannot_exceed_daily_booking_limit(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Limite Quotidienne',
            'constraints' => ['daily_booking_limit' => 2],
        ]);

        $date = now()->addDays(2)->format('Y-m-d');

        Reservation::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'date' => $date,
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
        ]);

        Reservation::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'date' => $date,
            'heure_debut' => '11:00',
            'heure_fin' => '12:00',
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Troisième réservation',
            'date' => $date,
            'heure_debut' => '14:00',
            'heure_fin' => '15:00',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['heure_debut']);
    }

    public function test_can_book_within_daily_booking_limit(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Limite Quotidienne',
            'constraints' => ['daily_booking_limit' => 3],
        ]);

        $date = now()->addDays(2)->format('Y-m-d');

        Reservation::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'date' => $date,
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Deuxième réservation',
            'date' => $date,
            'heure_debut' => '14:00',
            'heure_fin' => '15:00',
        ]);

        $response->assertStatus(201);
    }

    // ===========================
    // TESTS: Contrainte min_participants
    // ===========================

    public function test_cannot_book_room_with_insufficient_participants(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Grande Salle',
            'constraints' => ['min_participants' => 5],
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Petite réunion',
            'date' => now()->addDays(2)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'nombre_personnes' => 3,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['heure_debut']);
    }

    public function test_can_book_room_with_sufficient_participants(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Grande Salle',
            'constraints' => ['min_participants' => 5],
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Grande réunion',
            'date' => now()->addDays(2)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'nombre_personnes' => 8,
        ]);

        $response->assertStatus(201);
    }

    // ===========================
    // TESTS: Contraintes multiples
    // ===========================

    public function test_room_with_multiple_constraints_validates_all(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Contraintes Multiples',
            'constraints' => [
                'time_period' => 'morning',
                'days_allowed' => [1, 2, 3, 4, 5],
                'min_participants' => 3,
            ],
        ]);

        // Test 1: Échoue car après-midi
        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Test',
            'date' => now()->next('Monday')->format('Y-m-d'),
            'heure_debut' => '14:00',
            'heure_fin' => '15:00',
            'nombre_personnes' => 5,
        ]);
        $response->assertStatus(422);

        // Test 2: Échoue car samedi
        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Test',
            'date' => now()->next('Saturday')->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'nombre_personnes' => 5,
        ]);
        $response->assertStatus(422);

        // Test 3: Échoue car pas assez de participants
        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Test',
            'date' => now()->next('Monday')->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'nombre_personnes' => 2,
        ]);
        $response->assertStatus(422);

        // Test 4: Réussit car toutes les contraintes respectées
        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Test',
            'date' => now()->next('Monday')->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '11:00',
            'nombre_personnes' => 5,
        ]);
        $response->assertStatus(201);
    }

    // ===========================
    // TESTS: Salles sans contraintes
    // ===========================

    public function test_room_without_constraints_accepts_any_booking(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create([
            'nom' => 'Salle Sans Contraintes',
            'constraints' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'room_id' => $room->id,
            'titre' => 'Réunion flexible',
            'date' => now()->next('Saturday')->format('Y-m-d'),
            'heure_debut' => '14:00',
            'heure_fin' => '15:00',
            'nombre_personnes' => 1,
        ]);

        $response->assertStatus(201);
    }
}

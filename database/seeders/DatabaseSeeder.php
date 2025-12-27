<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Room;
use App\Models\Reservation;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer des utilisateurs de test
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'user',
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'role' => 'user',
        ]);

        User::factory()->create([
            'name' => 'Jean Dujardin',
            'email' => 'jean@example.com',
            'role' => 'user',
        ]);

        User::factory()->create([
            'name' => 'Jeanne D Arc',
            'email' => 'jeanne@example.com',
            'role' => 'user',
        ]);

        User::factory()->create([
            'name' => 'Bob Marley',
            'email' => 'bob@example.com',
            'role' => 'user',
        ]);

        User::factory()->create([
            'name' => 'Bobby Lapointe',
            'email' => 'bobby@example.com',
            'role' => 'user',
        ]);

        // Créer les salles depuis le dataset
        $rooms = [
            [
                "nom" => "Salle Atlantique",
                "capacite" => 4,
                "etage" => 1,
                "equipement" => ["Écran TV", "Tableau blanc"],
                "description" => "Petite salle idéale pour les réunions d'équipe",
                "constraints" => [
                    "time_period" => "morning",
                    "days_allowed" => [1, 2, 3, 4, 5, 6, 7], // Tous les jours
                ],
            ],
            [
                "nom" => "Salle Méditerranée",
                "capacite" => 6,
                "etage" => 1,
                "equipement" => ["Vidéoprojecteur", "Visioconférence", "Tableau blanc"],
                "description" => "Salle équipée pour les réunions hybrides",
                "constraints" => [
                    "time_period" => "afternoon",
                    "days_allowed" => [1, 2, 3, 4, 5, 6, 7], // Tous les jours
                ],
            ],
            [
                "nom" => "Salle Pacifique",
                "capacite" => 10,
                "etage" => 2,
                "equipement" => ["Vidéoprojecteur", "Visioconférence", "Paperboard", "Tableau blanc"],
                "description" => "Grande salle de réunion avec équipement complet",
                "constraints" => [
                    "days_allowed" => [1, 2, 3, 4, 6, 7], // Lundi à jeudi + samedi et dimanche (pas vendredi)
                ],
            ],
            [
                "nom" => "Salle Arctique",
                "capacite" => 8,
                "etage" => 2,
                "equipement" => ["Écran TV", "Visioconférence"],
                "description" => "Salle moderne avec baie vitrée",
                "constraints" => [
                    "days_allowed" => [1, 3], // Lundi et mercredi
                ],
            ],
            [
                "nom" => "Open Space - Zone A",
                "capacite" => 3,
                "etage" => 0,
                "equipement" => ["Tableau blanc mobile"],
                "description" => "Box de réunion informelle",
                "constraints" => [
                    "advance_booking_days" => 7,
                    "days_allowed" => [1, 2, 3, 4, 5, 6, 7], // Tous les jours
                ],
            ],
            [
                "nom" => "Salle du Conseil",
                "capacite" => 20,
                "etage" => 3,
                "equipement" => ["Vidéoprojecteur", "Visioconférence", "Système audio", "Écran tactile", "Tableau blanc"],
                "description" => "Grande salle pour présentations et conseil d'administration",
                "constraints" => [
                    "weekly_hours_quota" => 4,
                    "days_allowed" => [1, 2, 3, 4, 5, 6, 7], // Tous les jours
                ],
            ],
            [
                "nom" => "Salle Créative",
                "capacite" => 8,
                "etage" => 1,
                "equipement" => ["Paperboard", "Post-its", "Markers", "Tableau blanc"],
                "description" => "Salle dédiée aux sessions de brainstorming",
                "constraints" => [
                    "daily_booking_limit" => 1,
                    "days_allowed" => [1, 2, 3, 4, 5, 6, 7], // Tous les jours
                ],
            ],
            [
                "nom" => "Phone Box 1",
                "capacite" => 1,
                "etage" => 0,
                "equipement" => [],
                "description" => "Cabine individuelle pour appels téléphoniques",
                "constraints" => [
                    "days_allowed" => [1, 2, 3, 4, 5, 6, 7], // Tous les jours
                ],
            ],
            [
                "nom" => "Phone Box 2",
                "capacite" => 1,
                "etage" => 0,
                "equipement" => [],
                "description" => "Cabine individuelle pour appels téléphoniques",
                "constraints" => [
                    "days_allowed" => [1, 2, 3, 4, 5, 6, 7], // Tous les jours
                ],
            ],
            [
                "nom" => "Salle Formation",
                "capacite" => 15,
                "etage" => 2,
                "equipement" => ["Vidéoprojecteur", "Ordinateurs (10)", "Tableau blanc", "Paperboard"],
                "description" => "Salle équipée pour les formations et ateliers",
                "constraints" => [
                    "min_participants" => 3,
                    "days_allowed" => [1, 2, 3, 4, 5, 6, 7], // Tous les jours
                ],
            ],
        ];

        // Créer le dossier pour les images si nécessaire
        $imageDirectory = storage_path('app/public/room_images');
        if (!file_exists($imageDirectory)) {
            mkdir($imageDirectory, 0755, true);
        }

        // Créer les salles avec leurs images
        foreach ($rooms as $index => $roomData) {
            $room = Room::create($roomData);
            
            // Ajouter 2-3 images par salle (sauf Phone Box qui n'en ont qu'une)
            $imageCount = $room->capacite === 1 ? 1 : rand(2, 3);
            
            for ($i = 0; $i < $imageCount; $i++) {
                $imageName = "placeholder_{$room->id}_{$i}.jpg";
                $imagePath = "{$imageDirectory}/{$imageName}";
                
                // Télécharger l'image depuis picsum.photos (640x480)
                try {
                    $imageUrl = "https://picsum.photos/640/480?random={$room->id}{$i}";
                    $imageContent = file_get_contents($imageUrl);
                    
                    if ($imageContent !== false) {
                        file_put_contents($imagePath, $imageContent);
                        
                        $room->images()->create([
                            'path' => "room_images/{$imageName}",
                            'order' => $i,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Si le téléchargement échoue, continuer sans l'image
                    echo "Erreur lors du téléchargement de l'image pour {$room->nom}: {$e->getMessage()}\n";
                }
            }
        }

        // Ajouter des salles favorites pour les utilisateurs
        $johnDoe = User::find(2);
        $johnDoe->favoriteRooms()->attach([1, 3, 7]); // Atlantique, Pacifique, Créative

        $janeSmith = User::find(3);
        $janeSmith->favoriteRooms()->attach([6, 10, 2]); // Conseil, Formation, Méditerranée

        $jeanDujardin = User::find(4);
        $jeanDujardin->favoriteRooms()->attach([4, 5]); // Arctique, Open Space

        $jeanneDArc = User::find(5);
        $jeanneDArc->favoriteRooms()->attach([3, 6, 7]); // Pacifique, Conseil, Créative

        // Créer quelques réservations de test pour le même jour (dans 2 jours)
        $dateCommune = now()->addDays(2)->format('Y-m-d');
        
        // Réservation 1 - Matin (Salle Atlantique - matin uniquement)
        $reservation1 = Reservation::create([
            'room_id' => 1,
            'user_id' => 2, // John Doe
            'date' => $dateCommune,
            'heure_debut' => '09:00',
            'heure_fin' => '10:30',
            'titre' => 'Réunion d\'équipe',
            'description' => 'Point hebdomadaire',
            'nombre_personnes' => 3,
        ]);
        $reservation1->participants()->attach([2, 3, 4]); // John Doe (créateur), Jane Smith, Jean Dujardin

        // Réservation 2 - Matin (Salle Pacifique)
        $reservation2 = Reservation::create([
            'room_id' => 3,
            'user_id' => 3, // Jane Smith
            'date' => $dateCommune,
            'heure_debut' => '10:00',
            'heure_fin' => '11:30',
            'titre' => 'Brainstorming produit',
            'description' => 'Nouvelles idées pour Q1 2026',
            'nombre_personnes' => 5,
        ]);
        $reservation2->participants()->attach([3, 2, 4, 5, 6]); // Jane Smith (créatrice), John Doe, Jean Dujardin, Jeanne D'Arc, Bob Marley

        // Réservation 3 - Après-midi (Salle Méditerranée - après-midi uniquement)
        $reservation3 = Reservation::create([
            'room_id' => 2,
            'user_id' => 4, // Jean Dujardin
            'date' => $dateCommune,
            'heure_debut' => '14:00',
            'heure_fin' => '15:30',
            'titre' => 'Revue de projet',
            'description' => 'Avancement du projet Alpha',
            'nombre_personnes' => 4,
        ]);
        $reservation3->participants()->attach([4, 2, 3, 7]); // Jean Dujardin (créateur), John Doe, Jane Smith, Bobby Lapointe

        // Réservation 4 - Après-midi (Salle Formation - minimum 3 participants)
        $reservation4 = Reservation::create([
            'room_id' => 10,
            'user_id' => 5, // Jeanne D'Arc
            'date' => $dateCommune,
            'heure_debut' => '16:00',
            'heure_fin' => '17:30',
            'titre' => 'Formation Vue.js',
            'description' => 'Initiation au framework Vue 3',
            'nombre_personnes' => 5,
        ]);
        $reservation4->participants()->attach([5, 2, 3, 6, 7]); // Jeanne D'Arc (créatrice), John Doe, Jane Smith, Bob Marley, Bobby Lapointe

        // Réservations sur d'autres jours
        
        // Jour +1 - Salle Conseil (grande réunion)
        $reservation5 = Reservation::create([
            'room_id' => 6,
            'user_id' => 1, // Admin User
            'date' => now()->addDays(1)->format('Y-m-d'),
            'heure_debut' => '10:00',
            'heure_fin' => '12:00',
            'titre' => 'Conseil d\'administration',
            'description' => 'Réunion trimestrielle du conseil',
            'nombre_personnes' => 8,
        ]);
        $reservation5->participants()->attach([1, 2, 3, 4, 5, 6, 7]); // Admin + 6 participants

        // Jour +3 - Salle Créative (brainstorming)
        $reservation6 = Reservation::create([
            'room_id' => 7,
            'user_id' => 6, // Bob Marley
            'date' => now()->addDays(3)->format('Y-m-d'),
            'heure_debut' => '09:30',
            'heure_fin' => '11:00',
            'titre' => 'Session créativité',
            'description' => 'Brainstorming campagne marketing',
            'nombre_personnes' => 4,
        ]);
        $reservation6->participants()->attach([6, 2, 3, 5]); // Bob Marley (créateur), John Doe, Jane Smith, Jeanne D'Arc

        // Jour +5 - Phone Box 1 (appel individuel)
        $reservation7 = Reservation::create([
            'room_id' => 8,
            'user_id' => 7, // Bobby Lapointe
            'date' => now()->addDays(5)->format('Y-m-d'),
            'heure_debut' => '14:30',
            'heure_fin' => '15:00',
            'titre' => 'Appel client important',
            'description' => null,
            'nombre_personnes' => 1,
        ]);
        $reservation7->participants()->attach([7]); // Bobby Lapointe seul

        // Jour +7 - Salle Pacifique (présentation)
        $reservation8 = Reservation::create([
            'room_id' => 3,
            'user_id' => 2, // John Doe
            'date' => now()->addDays(7)->format('Y-m-d'),
            'heure_debut' => '15:00',
            'heure_fin' => '17:00',
            'titre' => 'Présentation résultats Q4',
            'description' => 'Présentation des résultats du trimestre',
            'nombre_personnes' => 8,
        ]);
        $reservation8->participants()->attach([2, 3, 4, 5, 6]); // John Doe (créateur) + 4 participants

        // Jour +10 - Open Space Zone A (réunion informelle)
        $reservation9 = Reservation::create([
            'room_id' => 5,
            'user_id' => 4, // Jean Dujardin
            'date' => now()->addDays(10)->format('Y-m-d'),
            'heure_debut' => '11:00',
            'heure_fin' => '11:30',
            'titre' => 'Point rapide équipe dev',
            'description' => 'Synchronisation rapide',
            'nombre_personnes' => 3,
        ]);
        $reservation9->participants()->attach([4, 5, 6]); // Jean Dujardin, Jeanne D'Arc, Bob Marley

        // Réservations passées
        
        // Jour -3 - Salle Atlantique (passée)
        $reservation10 = Reservation::create([
            'room_id' => 1,
            'user_id' => 3, // Jane Smith
            'date' => now()->subDays(3)->format('Y-m-d'),
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
            'titre' => 'Réunion hebdomadaire passée',
            'description' => 'Point d\'équipe de la semaine dernière',
            'nombre_personnes' => 3,
        ]);
        $reservation10->participants()->attach([3, 2, 4]); // Jane Smith, John Doe, Jean Dujardin

        // Jour -7 - Salle Méditerranée (passée)
        $reservation11 = Reservation::create([
            'room_id' => 2,
            'user_id' => 2, // John Doe
            'date' => now()->subDays(7)->format('Y-m-d'),
            'heure_debut' => '14:00',
            'heure_fin' => '16:30',
            'titre' => 'Workshop stratégie',
            'description' => 'Définition de la stratégie 2026',
            'nombre_personnes' => 6,
        ]);
        $reservation11->participants()->attach([2, 3, 4, 5, 6, 7]); // John Doe + 5 participants

        // Réservation en cours (aujourd'hui)
        // Calculer l'heure actuelle arrondie à 30 minutes avec la bonne timezone
        $now = now();
        $currentHour = $now->hour;
        $currentMinute = $now->minute < 30 ? 0 : 30;
        
        // Réservation qui commence 1h avant et finit 1h après (arrondi)
        $startTime = now()->setTime($currentHour, $currentMinute, 0)->subHour()->format('H:i');
        $endTime = now()->setTime($currentHour, $currentMinute, 0)->addHour()->format('H:i');
        
        $reservation12 = Reservation::create([
            'room_id' => 7,
            'user_id' => 5, // Jeanne D'Arc
            'date' => $now->format('Y-m-d'),
            'heure_debut' => $startTime,
            'heure_fin' => $endTime,
            'titre' => 'Réunion en cours',
            'description' => 'Réunion actuellement en cours',
            'nombre_personnes' => 4,
        ]);
        $reservation12->participants()->attach([5, 2, 3, 6]); // Jeanne D'Arc, John Doe, Jane Smith, Bob Marley

        // Créer les paramètres de l'application
        Setting::create([
            'key' => 'reservations.opening_time',
            'value' => '08:00',
            'type' => 'time',
            'title' => 'Heure d\'ouverture',
            'description' => 'Heure à laquelle les réservations peuvent commencer',
        ]);

        Setting::create([
            'key' => 'reservations.closing_time',
            'value' => '18:00',
            'type' => 'time',
            'title' => 'Heure de fermeture',
            'description' => 'Heure à laquelle les réservations doivent se terminer',
        ]);

        Setting::create([
            'key' => 'reservations.slot_duration',
            'value' => '30',
            'type' => 'integer',
            'title' => 'Durée des créneaux (minutes)',
            'description' => 'Durée d\'un créneau horaire en minutes',
        ]);

        Setting::create([
            'key' => 'reservations.min_advance_hours',
            'value' => '2',
            'type' => 'integer',
            'title' => 'Délai minimum de réservation (heures)',
            'description' => 'Nombre d\'heures minimum à l\'avance pour réserver',
        ]);

        Setting::create([
            'key' => 'reservations.max_advance_days',
            'value' => '30',
            'type' => 'integer',
            'title' => 'Délai maximum de réservation (jours)',
            'description' => 'Nombre de jours maximum à l\'avance pour réserver',
        ]);

        Setting::create([
            'key' => 'reservations.block_weekends',
            'value' => 'false',
            'type' => 'boolean',
            'title' => 'Bloquer les weekends',
            'description' => 'Empêcher les réservations le samedi et le dimanche',
        ]);
    }
}

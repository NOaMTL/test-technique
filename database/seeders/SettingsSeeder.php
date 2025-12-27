<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Paramètres des réservations
        Setting::set(
            'reservations.block_weekends',
            true,
            'boolean',
            'reservations',
            'Bloquer le weekend',
            'Les réservations ne sont pas autorisées le samedi et le dimanche'
        );

        Setting::set(
            'reservations.min_advance_hours',
            2,
            'integer',
            'reservations',
            'Réservation à l\'avance',
            'Nombre d\'heures minimum à l\'avance pour réserver'
        );

        Setting::set(
            'reservations.max_advance_days',
            90,
            'integer',
            'reservations',
            'Durée de planification',
            'Nombre de jours maximum à l\'avance pour réserver'
        );

        Setting::set(
            'reservations.opening_time',
            '08:00',
            'string',
            'reservations',
            'Heure d\'ouverture',
            'Heure de début de disponibilité des réservations'
        );

        Setting::set(
            'reservations.closing_time',
            '20:00',
            'string',
            'reservations',
            'Heure de fermeture',
            'Heure de fin de disponibilité des réservations'
        );

        Setting::set(
            'reservations.slot_duration',
            30,
            'integer',
            'reservations',
            'Durée des créneaux',
            'Durée minimale d\'un créneau de réservation en minutes'
        );

        // Paramètres des notifications
        Setting::set(
            'notifications.email_enabled',
            true,
            'boolean',
            'notifications',
            'Notifications par email',
            'Activer l\'envoi de notifications par email aux participants'
        );

        Setting::set(
            'notifications.reminder_hours',
            24,
            'integer',
            'notifications',
            'Rappel avant réservation',
            'Nombre d\'heures avant la réservation pour envoyer un rappel'
        );
    }
}

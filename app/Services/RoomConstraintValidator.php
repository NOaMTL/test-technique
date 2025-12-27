<?php

namespace App\Services;

use App\Models\Room;
use Carbon\Carbon;

class RoomConstraintValidator
{
    /**
     * Valide si une salle respecte toutes ses contraintes pour une date/heure donnée
     */
    public function validateRoomConstraints(
        Room $room, 
        Carbon $date, 
        string $heureDebut, 
        string $heureFin,
        int $nombrePersonnes = 1,
        ?int $userId = null,
        bool $skipMinParticipants = false
    ): array {
        $violations = [];
        
        // Vérifier les contraintes dynamiques JSON
        if ($room->constraints) {
            $constraintViolations = $this->validateDynamicConstraints(
                $room, 
                $date, 
                $heureDebut, 
                $heureFin,
                $nombrePersonnes,
                $userId,
                !$skipMinParticipants
            );
            $violations = array_merge($violations, $constraintViolations);
        }
        
        return $violations;
    }
    
    /**
     * Valide les contraintes dynamiques JSON
     */
    private function validateDynamicConstraints(
        Room $room, 
        Carbon $date, 
        string $heureDebut,
        string $heureFin,
        int $nombrePersonnes,
        ?int $userId,
        bool $includeMinParticipants = true
    ): array {
        $violations = [];
        $constraints = $room->constraints;
        
        // Période de la journée
        if (isset($constraints['time_period'])) {
            $hour = (int) substr($heureDebut, 0, 2);
            
            if ($constraints['time_period'] === 'morning' && $hour >= 12) {
                $violations[] = 'Cette salle est réservable uniquement le matin (avant 12h)';
            } elseif ($constraints['time_period'] === 'afternoon' && $hour < 12) {
                $violations[] = 'Cette salle est réservable uniquement l\'après-midi (après 12h)';
            }
        }
        
        // Jours autorisés
        if (isset($constraints['days_allowed']) && !empty($constraints['days_allowed'])) {
            $dayOfWeek = $date->dayOfWeekIso; // 1 = Lundi, 7 = Dimanche
            if (!in_array($dayOfWeek, $constraints['days_allowed'])) {
                $dayNames = ['', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
                $allowedDays = array_map(fn($d) => $dayNames[$d], $constraints['days_allowed']);
                $violations[] = 'Cette salle est réservable uniquement les ' . implode(', ', $allowedDays);
            }
        }
        
        // Réservation à l'avance
        if (isset($constraints['advance_booking_days'])) {
            $maxAdvanceDays = $constraints['advance_booking_days'];
            $now = Carbon::now();
            $daysInAdvance = $now->diffInDays($date, false);
            
            if ($daysInAdvance > $maxAdvanceDays) {
                $violations[] = "Cette salle peut être réservée maximum {$maxAdvanceDays} jour(s) à l'avance";
            }
        }
        
        // Quota hebdomadaire d'heures
        if (isset($constraints['weekly_hours_quota']) && $userId) {
            $maxHours = $constraints['weekly_hours_quota'];
            $weekStart = $date->copy()->startOfWeek();
            $weekEnd = $date->copy()->endOfWeek();
            
            $totalHours = \App\Models\Reservation::where('room_id', $room->id)
                ->where('user_id', $userId)
                ->whereBetween('date', [$weekStart, $weekEnd])
                ->get()
                ->sum(function($reservation) {
                    $debut = Carbon::createFromFormat('H:i', $reservation->heure_debut);
                    $fin = Carbon::createFromFormat('H:i', $reservation->heure_fin);
                    return $debut->diffInHours($fin, true);
                });
            
            $requestedHours = Carbon::createFromFormat('H:i', $heureDebut)
                ->diffInHours(Carbon::createFromFormat('H:i', $heureFin), true);
            
            if (($totalHours + $requestedHours) > $maxHours) {
                $remaining = max(0, $maxHours - $totalHours);
                $violations[] = "Quota hebdomadaire dépassé (max {$maxHours}h/semaine, reste {$remaining}h)";
            }
        }
        
        // Limite quotidienne de réservations
        if (isset($constraints['daily_booking_limit']) && $userId) {
            $maxBookings = $constraints['daily_booking_limit'];
            $bookingsToday = \App\Models\Reservation::where('room_id', $room->id)
                ->where('user_id', $userId)
                ->whereDate('date', $date)
                ->count();
            
            if ($bookingsToday >= $maxBookings) {
                $violations[] = "Limite quotidienne atteinte (max {$maxBookings} réservation(s) par jour)";
            }
        }
        
        // Nombre minimum de participants (seulement si demandé)
        if ($includeMinParticipants && isset($constraints['min_participants'])) {
            $minParticipants = $constraints['min_participants'];
            if ($nombrePersonnes < $minParticipants) {
                $violations[] = "Cette salle nécessite au minimum {$minParticipants} participant(s) (utilisateur inclus)";
            }
        }
        
        return $violations;
    }
    
    /**
     * Récupère la contrainte min_participants si elle existe
     */
    public function getMinParticipantsConstraint(Room $room): ?int {
        if ($room->constraints && isset($room->constraints['min_participants'])) {
            return $room->constraints['min_participants'];
        }
        return null;
    }
    
    /**
     * Vérifie si une salle est disponible (pas de violations)
     */
    public function isRoomAvailable(
        Room $room, 
        Carbon $date, 
        string $heureDebut, 
        string $heureFin,
        int $nombrePersonnes = 1,
        ?int $userId = null
    ): bool {
        $violations = $this->validateRoomConstraints(
            $room, 
            $date, 
            $heureDebut, 
            $heureFin,
            $nombrePersonnes,
            $userId
        );
        
        return empty($violations);
    }
    
    /**
     * Génère un texte lisible des contraintes d'une salle à partir du JSON
     */
    public function getConstraintsText(Room $room): ?string
    {
        $constraints = $this->getConstraintsArray($room);
        return !empty($constraints) ? implode(' • ', $constraints) : null;
    }
    
    /**
     * Génère un tableau de contraintes lisibles à partir du JSON
     */
    public function getConstraintsArray(Room $room): array
    {
        if (!$room->constraints || empty($room->constraints)) {
            return [];
        }
        
        $constraints = $room->constraints;
        $texts = [];
        
        // Période de la journée
        if (isset($constraints['time_period'])) {
            $periodTexts = [
                'morning' => 'Matin uniquement (avant 12h)',
                'afternoon' => 'Après-midi uniquement (après 12h)',
            ];
            if (isset($periodTexts[$constraints['time_period']])) {
                $texts[] = $periodTexts[$constraints['time_period']];
            }
        }
        
        // Jours autorisés
        if (isset($constraints['days_allowed']) && !empty($constraints['days_allowed'])) {
            $dayNames = ['', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
            $allowedDays = array_map(fn($d) => $dayNames[$d], $constraints['days_allowed']);
            
            // Si tous les jours sauf certains
            if (count($constraints['days_allowed']) >= 5) {
                $allDays = [1, 2, 3, 4, 5, 6, 7];
                $blockedDays = array_diff($allDays, $constraints['days_allowed']);
                if (!empty($blockedDays)) {
                    $blockedDaysText = implode(', ', array_map(fn($d) => $dayNames[$d], $blockedDays));
                    $texts[] = "Pas de réservation le " . $blockedDaysText;
                }
            } else {
                $texts[] = 'Réservable uniquement les ' . implode(', ', $allowedDays);
            }
        }
        
        // Réservation à l'avance
        if (isset($constraints['advance_booking_days'])) {
            $days = $constraints['advance_booking_days'];
            $texts[] = "Réservable maximum {$days} jour" . ($days > 1 ? 's' : '') . " à l'avance";
        }
        
        // Quota hebdomadaire
        if (isset($constraints['weekly_hours_quota'])) {
            $hours = $constraints['weekly_hours_quota'];
            $texts[] = "Limité à {$hours}h de réservation cumulée par utilisateur par semaine";
        }
        
        // Limite quotidienne
        if (isset($constraints['daily_booking_limit'])) {
            $limit = $constraints['daily_booking_limit'];
            $texts[] = "{$limit} réservation" . ($limit > 1 ? 's' : '') . " par jour maximum par utilisateur";
        }
        
        // Participants minimum
        if (isset($constraints['min_participants'])) {
            $min = $constraints['min_participants'];
            $texts[] = "Minimum {$min} participant" . ($min > 1 ? 's' : '') . " (utilisateur inclus)";
        }
        
        return $texts;
    }
}

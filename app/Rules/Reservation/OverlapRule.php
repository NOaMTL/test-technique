<?php

namespace App\Rules\Reservation;

use Closure;
use App\Models\Reservation;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class OverlapRule implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $roomId = $this->data['room_id'] ?? null;
        $date = $this->data['date'] ?? null;
        $heureDebut = $this->data['heure_debut'] ?? null;
        $heureFin = $this->data['heure_fin'] ?? null;

        // Ne valider que si tous les champs sont présents
        if (!$roomId || !$date || !$heureDebut || !$heureFin) {
            return;
        }

        Log::info('Vérification de chevauchement', [
            'room_id' => $roomId,
            'date' => $date,
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
        ]);

        $query = Reservation::where('room_id', $roomId)
            ->whereDate('date', $date)
            ->where(function ($query) use ($heureDebut, $heureFin) {
                // Chevauchement si :
                // 1. La nouvelle réservation commence avant la fin d'une existante
                // ET
                // 2. La nouvelle réservation se termine après le début d'une existante
                $query->where('heure_debut', '<', $heureFin)
                      ->where('heure_fin', '>', $heureDebut);
            });

        // Si c'est une mise à jour, exclure la réservation actuelle
        $reservation = request()->route('reservation');
        if ($reservation) {
            $query->where('id', '!=', $reservation->id);
        }

        $conflictingReservations = $query->get();
        Log::info('Réservations en conflit', [
            'count' => $conflictingReservations->count(),
            'reservations' => $conflictingReservations->toArray()
        ]);

        if ($query->exists()) {
            $fail('Cette salle est déjà réservée sur ce créneau horaire.');
        }
    }
}

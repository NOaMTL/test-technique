<?php

namespace App\Rules\Reservation;

use Closure;
use App\Models\Room;
use App\Services\RoomConstraintValidator;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class RoomConstraintRule implements ValidationRule, DataAwareRule
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
        $nombrePersonnes = $this->data['nombre_personnes'] ?? 1;
        $userId = auth()->id();

        // Ne valider que si tous les champs nécessaires sont présents
        if (!$roomId || !$date || !$heureDebut || !$heureFin) {
            return;
        }

        $room = Room::find($roomId);
        
        if (!$room) {
            return;
        }

        try {
            $dateCarbon = Carbon::parse($date);
        } catch (\Exception $e) {
            return; // Les erreurs de format seront gérées par d'autres règles
        }

        // Utiliser le service de validation des contraintes
        $validator = new RoomConstraintValidator();
        $violations = $validator->validateRoomConstraints(
            $room,
            $dateCarbon,
            $heureDebut,
            $heureFin,
            $nombrePersonnes,
            $userId
        );

        // Si des violations sont détectées, échouer la validation
        if (!empty($violations)) {
            $fail(implode(' ', $violations));
        }
    }
}

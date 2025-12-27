<?php

namespace App\Services\Rooms;

use App\Models\Room;
use App\Services\Cache\CacheInvalidationService;
use App\Services\RoomConstraintValidator;
use Illuminate\Database\Eloquent\Collection;

class RoomService
{
    public function __construct(
        protected CacheInvalidationService $cacheService,
        protected RoomConstraintValidator $constraintValidator,
    ) {}

    /**
     * Get all active rooms with images.
     *
     * @return Collection<int, Room>
     */
    public function getActiveRooms(): Collection
    {
        return Room::with('images')
            ->where('is_active', true)
            ->orderBy('nom')
            ->get();
    }

    /**
     * Get all rooms (admin).
     *
     * @return Collection<int, Room>
     */
    public function getAllRooms(): Collection
    {
        return Room::with('images')
            ->orderBy('nom')
            ->get()
            ->map(function ($room) {
                $room->constraints_array = $this->constraintValidator->getConstraintsArray($room);
                return $room;
            });
    }

    /**
     * Get unique equipment list.
     *
     * @return array<int, string>
     */
    public function getUniqueEquipments(): array
    {
        return Room::whereNotNull('equipement')
            ->where('is_active', true)
            ->pluck('equipement')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get unique floor list.
     *
     * @return array<int, int>
     */
    public function getUniqueFloors(): array
    {
        return Room::where('is_active', true)
            ->distinct()
            ->orderBy('etage')
            ->pluck('etage')
            ->all();
    }

    /**
     * Find room by ID with images.
     *
     * @param int $id
     * @return Room|null
     */
    public function findWithImages(int $id): ?Room
    {
        return Room::with('images')->find($id);
    }

    /**
     * Search rooms by criteria.
     *
     * @param array<string, mixed> $criteria
     * @return Collection<int, Room>
     */
    public function search(array $criteria): Collection
    {
        $query = Room::query()->where('is_active', true)->with('images');

        if (isset($criteria['capacite'])) {
            $query->where('capacite', '>=', $criteria['capacite']);
        }

        if (isset($criteria['etage'])) {
            $query->where('etage', $criteria['etage']);
        }

        if (isset($criteria['equipement']) && is_array($criteria['equipement'])) {
            foreach ($criteria['equipement'] as $equipment) {
                $query->whereJsonContains('equipement', $equipment);
            }
        }

        return $query->orderBy('nom')->get();
    }

    /**
     * Check if room is available for booking.
     *
     * @param Room $room
     * @param string $date
     * @param string $heureDebut
     * @param string $heureFin
     * @param int|null $excludeReservationId
     * @return bool
     */
    public function isAvailable(
        Room $room,
        string $date,
        string $heureDebut,
        string $heureFin,
        ?int $excludeReservationId = null
    ): bool {
        $query = $room->reservations()
            ->where('date', $date)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($heureDebut, $heureFin) {
                $q->whereBetween('heure_debut', [$heureDebut, $heureFin])
                    ->orWhereBetween('heure_fin', [$heureDebut, $heureFin])
                    ->orWhere(function ($q2) use ($heureDebut, $heureFin) {
                        $q2->where('heure_debut', '<=', $heureDebut)
                            ->where('heure_fin', '>=', $heureFin);
                    });
            });

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->count() === 0;
    }
}

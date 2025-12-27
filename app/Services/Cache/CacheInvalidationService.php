<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

class CacheInvalidationService
{
    /**
     * Invalidate all room-related caches.
     *
     * @return void
     */
    public function invalidateRoomCaches(): void
    {
        Cache::forget('rooms.active.with.images');
        Cache::forget('rooms.equipments.unique');
        Cache::forget('rooms.floors.unique');
    }

    /**
     * Invalidate all reservation-related caches.
     *
     * @return void
     */
    public function invalidateReservationCaches(): void
    {
        Cache::forget('reservations.upcoming');
        Cache::forget('reservations.user');
    }

    /**
     * Invalidate all user-related caches.
     *
     * @return void
     */
    public function invalidateUserCaches(): void
    {
        Cache::forget('users.list');
        Cache::forget('users.active');
    }

    /**
     * Invalidate specific cache by type.
     *
     * @param string $type
     * @return void
     */
    public function invalidateByType(string $type): void
    {
        match ($type) {
            'rooms' => $this->invalidateRoomCaches(),
            'reservations' => $this->invalidateReservationCaches(),
            'users' => $this->invalidateUserCaches(),
            'equipments' => Cache::forget('rooms.equipments.unique'),
            'floors' => Cache::forget('rooms.floors.unique'),
            'favorites' => Cache::forget('favorites.user'),
            default => null,
        };
    }

    /**
     * Clear all application cache.
     *
     * @return void
     */
    public function clearAll(): void
    {
        Cache::flush();
    }
}

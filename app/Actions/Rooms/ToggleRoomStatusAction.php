<?php

namespace App\Actions\Rooms;

use App\Models\Room;
use App\Services\Cache\CacheInvalidationService;

class ToggleRoomStatusAction
{
    public function __construct(
        protected CacheInvalidationService $cacheService,
    ) {}

    /**
     * Toggle room active status.
     *
     * @param Room $room
     * @return Room
     */
    public function execute(Room $room): Room
    {
        $room->update(['is_active' => !$room->is_active]);

        // Invalidate caches
        $this->cacheService->invalidateRoomCaches();

        return $room->fresh();
    }
}

<?php

namespace App\Actions\Rooms;

use App\Models\Room;
use App\Services\Cache\CacheInvalidationService;

class CreateRoomAction
{
    public function __construct(
        protected CacheInvalidationService $cacheService,
    ) {}

    /**
     * Create a new room.
     *
     * @param array<string, mixed> $data
     * @return Room
     */
    public function execute(array $data): Room
    {
        $room = Room::create([
            'nom' => $data['nom'],
            'capacite' => $data['capacite'],
            'etage' => $data['etage'],
            'equipement' => $data['equipement'] ?? null,
            'description' => $data['description'] ?? null,
            'constraints' => $data['constraints'] ?? null,
        ]);

        // Handle images if provided
        if (isset($data['images']) && is_array($data['images'])) {
            $this->handleImages($room, $data['images']);
        }

        // Invalidate caches
        $this->cacheService->invalidateRoomCaches();

        return $room->fresh('images');
    }

    /**
     * Handle room images upload.
     *
     * @param Room $room
     * @param array<int, \Illuminate\Http\UploadedFile> $images
     * @return void
     */
    protected function handleImages(Room $room, array $images): void
    {
        foreach ($images as $index => $image) {
            $path = $image->store('rooms', 'public');
            $room->images()->create([
                'path' => $path,
                'order' => $index,
            ]);
        }
    }
}

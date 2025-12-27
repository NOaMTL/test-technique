<?php

namespace App\Actions\Rooms;

use App\Models\Room;
use App\Services\Cache\CacheInvalidationService;
use Illuminate\Support\Facades\Storage;

class UpdateRoomAction
{
    public function __construct(
        protected CacheInvalidationService $cacheService,
    ) {}

    /**
     * Update an existing room.
     *
     * @param Room $room
     * @param array<string, mixed> $data
     * @return Room
     */
    public function execute(Room $room, array $data): Room
    {
        // Update basic room data
        $room->update([
            'nom' => $data['nom'],
            'capacite' => $data['capacite'],
            'etage' => $data['etage'],
            'equipement' => $data['equipement'] ?? null,
            'description' => $data['description'] ?? null,
            'constraints' => $data['constraints'] ?? null,
        ]);

        // Handle image deletion
        if (isset($data['delete_images']) && is_array($data['delete_images'])) {
            $this->deleteImages($room, $data['delete_images']);
        }

        // Handle existing images order
        if (isset($data['existing_images_order']) && is_array($data['existing_images_order'])) {
            $this->updateImagesOrder($room, $data['existing_images_order']);
        }

        // Handle new images upload
        if (isset($data['images']) && is_array($data['images'])) {
            $this->uploadNewImages($room, $data['images']);
        }

        // Invalidate caches
        $this->cacheService->invalidateRoomCaches();

        return $room->fresh('images');
    }

    /**
     * Delete specified images.
     *
     * @param Room $room
     * @param array<int, int> $imageIds
     * @return void
     */
    protected function deleteImages(Room $room, array $imageIds): void
    {
        foreach ($imageIds as $imageId) {
            $image = $room->images()->find($imageId);
            if ($image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }
        }
    }

    /**
     * Update the order of existing images.
     *
     * @param Room $room
     * @param array<int, int> $imageIds
     * @return void
     */
    protected function updateImagesOrder(Room $room, array $imageIds): void
    {
        foreach ($imageIds as $index => $imageId) {
            $room->images()->where('id', $imageId)->update(['order' => $index]);
        }
    }

    /**
     * Upload new images.
     *
     * @param Room $room
     * @param array<int, \Illuminate\Http\UploadedFile> $images
     * @return void
     */
    protected function uploadNewImages(Room $room, array $images): void
    {
        $currentMaxOrder = $room->images()->max('order') ?? -1;
        
        foreach ($images as $index => $image) {
            $path = $image->store('rooms', 'public');
            $room->images()->create([
                'path' => $path,
                'order' => $currentMaxOrder + $index + 1,
            ]);
        }
    }
}

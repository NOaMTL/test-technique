<?php

namespace App\Actions\Rooms;

use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Support\Facades\Storage;

class ManageRoomImagesAction
{
    /**
     * Update images order for a room.
     *
     * @param Room $room
     * @param array<int, int> $order
     * @return void
     */
    public function updateOrder(Room $room, array $order): void
    {
        foreach ($order as $index => $imageId) {
            $room->images()
                ->where('id', $imageId)
                ->update(['order' => $index]);
        }
    }

    /**
     * Delete a specific image.
     *
     * @param Room $room
     * @param RoomImage $image
     * @return void
     */
    public function deleteImage(Room $room, RoomImage $image): void
    {
        // Verify the image belongs to this room
        if ($image->room_id !== $room->id) {
            throw new \InvalidArgumentException('Image does not belong to this room');
        }

        // Delete from storage
        Storage::disk('public')->delete($image->path);
        
        // Delete from database
        $image->delete();
    }

    /**
     * Upload new images for a room.
     *
     * @param Room $room
     * @param array<int, \Illuminate\Http\UploadedFile> $images
     * @return void
     */
    public function uploadImages(Room $room, array $images): void
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

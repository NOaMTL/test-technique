<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Rooms\CreateRoomAction;
use App\Actions\Rooms\ManageRoomImagesAction;
use App\Actions\Rooms\ToggleRoomStatusAction;
use App\Actions\Rooms\UpdateRoomAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rooms\StoreRoomRequest;
use App\Http\Requests\Rooms\UpdateRoomRequest;
use App\Models\Room;
use App\Services\Rooms\RoomService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomController extends Controller
{
    public function __construct(
        protected RoomService $roomService,
        protected CreateRoomAction $createRoomAction,
        protected UpdateRoomAction $updateRoomAction,
        protected ToggleRoomStatusAction $toggleRoomStatusAction,
        protected ManageRoomImagesAction $manageRoomImagesAction,
    ) {}

    /**
     * Display a listing of rooms (admin).
     */
    public function index()
    {
        $rooms = $this->roomService->getAllRooms();

        return Inertia::render('Admin/Rooms/Index', [
            'rooms' => $rooms,
        ]);
    }

    /**
     * Show the form for creating a new room.
     */
    public function create()
    {
        return Inertia::render('Admin/Rooms/Create');
    }

    /**
     * Store a newly created room.
     */
    public function store(StoreRoomRequest $request)
    {
        $room = $this->createRoomAction->execute($request->validated());

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Salle créée avec succès');
    }

    /**
     * Show the form for editing the specified room.
     */
    public function edit(Room $room)
    {
        $room->load('images');
        
        return Inertia::render('Admin/Rooms/Edit', [
            'room' => $room,
        ]);
    }

    /**
     * Update the specified room.
     */
    public function update(UpdateRoomRequest $request, Room $room)
    {
        $this->updateRoomAction->execute($room, $request->validated());

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Salle mise à jour avec succès');
    }

    /**
     * Toggle room active status.
     */
    public function toggleActive(Room $room)
    {
        $this->toggleRoomStatusAction->execute($room);

        $status = $room->is_active ? 'activée' : 'désactivée';
        
        $rooms = $this->roomService->getAllRooms();
        
        return Inertia::render('Admin/Rooms/Index', [
            'rooms' => $rooms,
        ])->with('success', "Salle {$status} avec succès");
    }

    /**
     * Remove the specified room (soft delete via is_active).
     */
    public function destroy(Room $room)
    {
        // On désactive plutôt que de supprimer pour garder l'historique
        $this->toggleRoomStatusAction->execute($room);

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Salle désactivée avec succès');
    }

    /**
     * Update only the order of room images (API endpoint for drag & drop).
     */
    public function updateImagesOrder(Request $request, Room $room)
    {
        $validated = $request->validate([
            'existing_images_order' => 'required|array',
            'existing_images_order.*' => 'integer|exists:room_images,id',
        ]);

        $this->manageRoomImagesAction->updateOrder($room, $validated['existing_images_order']);

        return response()->json([
            'success' => true,
            'message' => 'Ordre des images mis à jour',
        ]);
    }

    /**
     * Delete a specific room image (API endpoint for immediate deletion).
     */
    public function deleteImage(Request $request, Room $room, $imageId)
    {
        $image = $room->images()->find($imageId);
        
        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image non trouvée',
            ], 404);
        }

        $this->manageRoomImagesAction->deleteImage($room, $image);

        return response()->json([
            'success' => true,
            'message' => 'Image supprimée avec succès',
        ]);
    }
}

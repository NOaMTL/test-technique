<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\RoomConstraintValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Cache permanent des salles avec images (invalidé automatiquement lors des modifications)
        $rooms = Cache::rememberForever('rooms.active.with.images', function() {
            return Room::query()->where('is_active', true)->with('images')->orderBy('nom')->get();
        });

        // Appliquer les filtres sur la collection en cache
        $query = $rooms;

        // Filtrer par capacité minimale
        if ($request->has('capacite_min')) {
            $query = $query->where('capacite', '>=', $request->capacite_min);
        }

        // Filtrer par équipement
        if ($request->has('equipement')) {
            $equipements = is_array($request->equipement) 
                ? $request->equipement 
                : explode(',', $request->equipement);
            
            foreach ($equipements as $equipement) {
                $query = $query->filter(function($room) use ($equipement) {
                    return in_array(trim($equipement), $room->equipement ?? []);
                });
            }
        }

        // Filtrer par étage
        if ($request->has('etage')) {
            $query = $query->where('etage', $request->etage);
        }

        $rooms = $query->values();

        // Ajouter l'information si la salle est favorite pour l'utilisateur connecté
        $user = $request->user();
        if ($user) {
            $favoriteRoomIds = $user->favoriteRooms()->pluck('rooms.id')->toArray();
            
            $rooms = $rooms->map(function($room) use ($favoriteRoomIds) {
                $room->is_favorite = in_array($room->id, $favoriteRoomIds);
                return $room;
            });

            // Trier : favoris d'abord, puis par nom
            $rooms = $rooms->sortBy([
                fn($a, $b) => $b->is_favorite <=> $a->is_favorite,
                fn($a, $b) => $a->nom <=> $b->nom,
            ])->values();
        }

        return response()->json($rooms);
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room)
    {
        return response()->json($room);
    }

    /**
     * Toggle favorite status for a room
     */
    public function toggleFavorite(Request $request, Room $room)
    {
        $user = $request->user();
        
        if ($user->favoriteRooms()->where('room_id', $room->id)->exists()) {
            $user->favoriteRooms()->detach($room->id);
            Cache::forget("user.{$user->id}.favorites");
            return response()->json(['message' => 'Retiré des favoris', 'is_favorite' => false]);
        } else {
            $user->favoriteRooms()->attach($room->id);
            Cache::forget("user.{$user->id}.favorites");
            return response()->json(['message' => 'Ajouté aux favoris', 'is_favorite' => true]);
        }
    }

    /**
     * Get user's favorite rooms
     */
    public function favorites(Request $request)
    {
        $userId = $request->user()->id;
        
        $favorites = Cache::remember("user.{$userId}.favorites", 600, function() use ($request) {
            return $request->user()->favoriteRooms()->with('images')->get();
        });
        
        return response()->json($favorites);
    }

    /**
     * Get unique equipments list
     */
    public function equipments()
    {
        $equipments = Cache::rememberForever('rooms.equipments.unique', function() {
            return Room::whereNotNull('equipement')
                ->where('is_active', true)
                ->pluck('equipement')
                ->flatten()
                ->unique()
                ->sort()
                ->values();
        });
        
        return response()->json($equipments);
    }

    /**
     * Get unique floors list
     */
    public function floors()
    {
        $floors = Cache::rememberForever('rooms.floors.unique', function() {
            return Room::where('is_active', true)
                ->distinct()
                ->pluck('etage')
                ->sort()
                ->values();
        });
        
        return response()->json($floors);
    }

    /**
     * Get available rooms based on date, time and criteria
     */
    public function available(Request $request)
    {
        // Seuls les admins peuvent consulter les dates passées
        $isAdmin = $request->user() && $request->user()->role === 'admin';
        $dateRule = $isAdmin ? 'required|date' : 'required|date|after_or_equal:today';
        
        $request->validate([
            'date' => $dateRule,
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'capacite_min' => 'nullable|integer|min:1',
            'equipements' => 'nullable|array',
            'nombre_personnes' => 'nullable|integer|min:1',
            'exclude_reservation_id' => 'nullable|integer', // Pour exclure une réservation lors de la modification
        ]);

        try {
            $date = \Carbon\Carbon::parse($request->date);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Format de date invalide'], 422);
        }
        
        $constraintValidator = new RoomConstraintValidator();
        $userId = $request->user()?->id;

        $query = Room::query()->where('is_active', true)->with('images');

        // Filtrer par capacité minimale
        if ($request->filled('capacite_min')) {
            $query->where('capacite', '>=', $request->capacite_min);
        }

        // Filtrer par équipements
        if ($request->filled('equipements') && is_array($request->equipements) && count($request->equipements) > 0) {
            foreach ($request->equipements as $equipement) {
                $query->whereJsonContains('equipement', $equipement);
            }
        }

        $rooms = $query->get();

        // Filtrer par contraintes et disponibilité
        $availableRooms = $rooms->map(function ($room) use ($date, $request, $constraintValidator, $userId) {
            $nombrePersonnes = $request->filled('nombre_personnes') ? $request->nombre_personnes : 1;
            
            // Vérifier les contraintes de la salle (sans min_participants pour ne pas bloquer l'affichage)
            $violations = $constraintValidator->validateRoomConstraints(
                $room,
                $date,
                $request->heure_debut,
                $request->heure_fin,
                $nombrePersonnes,
                $userId,
                true // Skip min_participants validation
            );
            
            // Ajouter l'info min_participants séparément
            $minParticipants = $constraintValidator->getMinParticipantsConstraint($room);
            if ($minParticipants) {
                $room->min_participants = $minParticipants;
            }
            
            // Si des contraintes sont violées, marquer mais ne pas filtrer
            if (!empty($violations)) {
                $room->constraint_violations = $violations;
                $room->is_available = false;
                return $room;
            }

            // Vérifier les chevauchements de réservation
            $conflictQuery = \App\Models\Reservation::where('room_id', $room->id)
                ->whereDate('date', $request->date)
                ->where(function ($query) use ($request) {
                    // Chevauchement si :
                    // 1. La réservation existante commence avant la fin de la nouvelle
                    // ET
                    // 2. La réservation existante se termine après le début de la nouvelle
                    $query->where('heure_debut', '<', $request->heure_fin)
                          ->where('heure_fin', '>', $request->heure_debut);
                });

            // Exclure une réservation spécifique (utile lors de la modification)
            if ($request->filled('exclude_reservation_id')) {
                $conflictQuery->where('id', '!=', $request->exclude_reservation_id);
            }

            $hasConflict = $conflictQuery->exists();
            $room->is_available = !$hasConflict;

            return $room;
        })->filter(function ($room) {
            // Filtrer uniquement les salles non disponibles (conflits ou contraintes)
            return $room->is_available;
        });

        // Ajouter l'information si la salle est favorite pour l'utilisateur connecté
        $user = $request->user();
        if ($user) {
            $favoriteRoomIds = $user->favoriteRooms()->pluck('rooms.id')->toArray();
            
            $availableRooms = $availableRooms->map(function($room) use ($favoriteRoomIds) {
                $room->is_favorite = in_array($room->id, $favoriteRoomIds);
                return $room;
            });
        }

        return response()->json($availableRooms->values());
    }
}

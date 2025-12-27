<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReservationController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of all reservations.
     */
    public function index(Request $request)
    {
        // Générer une clé de cache basée sur les paramètres de requête
        $cacheKey = 'reservations.admin.' . md5(json_encode($request->all()));
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request) {
            $query = Reservation::with(['room.images', 'user', 'participants']);

            // Filtrer par date
            if ($request->has('date')) {
                $query->whereDate('date', $request->date);
            }

            // Filtrer par salles (support du multiselect)
            if ($request->has('room_ids')) {
                $roomIds = $request->room_ids;
                $query->whereIn('room_id', $roomIds);
            } elseif ($request->has('room_id')) {
                // Backward compatibility avec l'ancien filtre simple
                $query->where('room_id', $request->room_id);
            }

            // Filtrer par mois et année
            if ($request->has('month') && $request->has('year')) {
                $month = $request->month;
                $year = $request->year;
                $query->whereYear('date', $year)
                      ->whereMonth('date', $month);
            }

            // Filtrer par période
            if ($request->has('date_debut') && $request->has('date_fin')) {
                $query->whereBetween('date', [$request->date_debut, $request->date_fin]);
            }

            $query->orderBy('date')->orderBy('heure_debut');

            // Pagination si demandée
            if ($request->has('paginate') && $request->paginate === 'true') {
                $perPage = $request->per_page ?? 15;
                return response()->json($query->paginate($perPage));
            }

            return response()->json($query->get());
        });
    }

    /**
     * Get user's reservations.
     */
    public function userReservations(Request $request)
    {
        $query = Reservation::with(['room.images', 'participants'])
            ->where('user_id', $request->user()->id);

        // Filtre par salles
        if ($request->has('room_ids') && is_array($request->room_ids) && count($request->room_ids) > 0) {
            $query->whereIn('room_id', $request->room_ids);
        }

        // Filtre par date de début
        if ($request->has('date_start') && $request->date_start) {
            $dateStart = Carbon::parse($request->date_start)->startOfDay();
            $query->where('date', '>=', $dateStart);
        }

        // Filtre par date de fin
        if ($request->has('date_end') && $request->date_end) {
            $dateEnd = Carbon::parse($request->date_end)->endOfDay();
            $query->where('date', '<=', $dateEnd);
        }

        // Filtre par recherche (titre, salle, description)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('room', function($roomQuery) use ($search) {
                      $roomQuery->where('nom', 'like', "%{$search}%");
                  });
            });
        }

        $query->orderBy('date', 'desc')
              ->orderBy('heure_debut', 'desc');

        // Pagination
        $perPage = $request->per_page ?? 10;
        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created reservation.
     */
    public function store(StoreReservationRequest $request)
    {
        $validated = $request->validated();
        
        // Créer la réservation
        $reservation = Reservation::create([
            'room_id' => $validated['room_id'],
            'user_id' => $request->user()->id,
            'date' => $validated['date'],
            'heure_debut' => $validated['heure_debut'],
            'heure_fin' => $validated['heure_fin'],
            'titre' => $validated['titre'] ?? null,
            'description' => $validated['description'] ?? null,
            'nombre_personnes' => $validated['nombre_personnes'] ?? null,
        ]);

        // Ajouter les participants si fournis
        if (isset($validated['participants'])) {
            $reservation->participants()->attach($validated['participants']);
            
            // TODO: Implémenter l'envoi d'email aux participants
            if ($request->get('notify_participants', false)) {
                $this->notifyParticipants($reservation);
            }
        }

        // Charger les relations
        $reservation->load(['room', 'user', 'participants']);

        // Invalider le cache des réservations
        $this->clearReservationsCache();

        return response()->json($reservation, 201);
    }
    
    /**
     * Envoyer une notification par email aux participants.
     * TODO: Implémenter l'envoi réel d'emails
     */
    private function notifyParticipants(Reservation $reservation)
    {
        // Placeholder pour la fonctionnalité d'envoi d'email
        // 
        // Exemple d'implémentation future :
        // 
        // foreach ($reservation->participants as $participant) {
        //     Mail::to($participant->email)->send(
        //         new ReservationNotification($reservation, $participant)
        //     );
        // }
        //
        // Ou utiliser une notification Laravel :
        // Notification::send(
        //     $reservation->participants,
        //     new ReservationCreated($reservation)
        // );
        
        Log::info('Notification email pour la réservation #' . $reservation->id, [
            'participants' => $reservation->participants->pluck('email')->toArray(),
            'reservation' => [
                'titre' => $reservation->titre,
                'date' => $reservation->date,
                'heure_debut' => $reservation->heure_debut,
                'heure_fin' => $reservation->heure_fin,
                'room' => $reservation->room->nom ?? null,
            ]
        ]);
    }

    /**
     * Display the specified reservation.
     */
    public function show(Reservation $reservation)
    {
        $reservation->load(['room', 'user', 'participants']);
        return response()->json($reservation);
    }

    /**
     * Update the specified reservation.
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation)
    {
        // Vérifier l'autorisation via la Policy
        $this->authorize('update', $reservation);

        // Vérifier que la réservation est à venir
        if ($reservation->date < now()->toDateString()) {
            return response()->json(['message' => 'Impossible de modifier une réservation passée'], 422);
        }

        $validated = $request->validated();
        
        $reservation->update([
            'room_id' => $validated['room_id'],
            'date' => $validated['date'],
            'heure_debut' => $validated['heure_debut'],
            'heure_fin' => $validated['heure_fin'],
            'titre' => $validated['titre'] ?? $reservation->titre,
            'description' => $validated['description'] ?? $reservation->description,
            'nombre_personnes' => $validated['nombre_personnes'] ?? $reservation->nombre_personnes,
        ]);

        // Mettre à jour les participants
        if (isset($validated['participants'])) {
            $reservation->participants()->sync($validated['participants']);
            
            // TODO: Implémenter l'envoi d'email aux participants pour les modifications
            if ($request->get('notify_participants', false)) {
                $this->notifyParticipantsUpdate($reservation);
            }
        }

        $reservation->load(['room', 'user', 'participants']);

        // Invalider le cache des réservations
        $this->clearReservationsCache();

        return response()->json($reservation);
    }
    
    /**
     * Notifier les participants d'une modification de réservation.
     * TODO: Implémenter l'envoi réel d'emails
     */
    private function notifyParticipantsUpdate(Reservation $reservation)
    {
        // Placeholder pour la fonctionnalité d'envoi d'email de modification
        // 
        // Exemple d'implémentation future :
        // 
        // foreach ($reservation->participants as $participant) {
        //     Mail::to($participant->email)->send(
        //         new ReservationUpdated($reservation, $participant)
        //     );
        // }
        // Ou utiliser une notification Laravel : https://laravel.com/docs/12.x/notifications
        // En utilisant le systeme de Queue
        
        Log::info('Notification de modification pour la réservation #' . $reservation->id, [
            'participants' => $reservation->participants->pluck('email')->toArray(),
            'reservation' => [
                'titre' => $reservation->titre,
                'date' => $reservation->date,
                'heure_debut' => $reservation->heure_debut,
                'heure_fin' => $reservation->heure_fin,
                'room' => $reservation->room->nom ?? null,
            ]
        ]);
    }

    /**
     * Remove the specified reservation.
     */
    public function destroy(Request $request, Reservation $reservation)
    {
        // Vérifier l'autorisation via la Policy
        $this->authorize('delete', $reservation);

        // Vérifier que la réservation est à venir
        if ($reservation->date < now()->toDateString()) {
            return response()->json(['message' => 'Impossible de supprimer une réservation passée'], 422);
        }

        $reservation->delete();

        // Invalider le cache des réservations
        $this->clearReservationsCache();

        return response()->json(['message', 'Réservation supprimée avec succès']);
    }

    /**
     * Check availability for a room at a given time.
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'reservation_id' => 'nullable|exists:reservations,id',
        ]);

        $conflicts = Reservation::where('room_id', $request->room_id)
            ->where('date', $request->date)
            ->where(function ($query) use ($request) {
                $query->whereBetween('heure_debut', [$request->heure_debut, $request->heure_fin])
                    ->orWhereBetween('heure_fin', [$request->heure_debut, $request->heure_fin])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('heure_debut', '<=', $request->heure_debut)
                          ->where('heure_fin', '>=', $request->heure_fin);
                    });
            });

        // Exclure la réservation en cours de modification
        if ($request->has('reservation_id')) {
            $conflicts->where('id', '!=', $request->reservation_id);
        }

        $hasConflicts = $conflicts->exists();

        return response()->json([
            'available' => !$hasConflicts,
            'conflicts' => $hasConflicts ? $conflicts->with('user')->get() : [],
        ]);
    }

    /**
     * Invalider tous les caches de réservations.
     */
    private function clearReservationsCache()
    {
        // Avec le driver file de Laravel, on ne peut pas facilement vider par pattern
        // La solution la plus simple est de vider tout le cache ou d'utiliser un tag
        
        // Option 1: Vider tout le cache (simple mais brutal)
        Cache::flush();
        
        // Option 2 (préférée si vous utilisez Redis/Memcached):
        // Cache::tags(['reservations'])->flush();
        
        // Note: Pour utiliser les tags, modifiez aussi la méthode index() :
        // return Cache::tags(['reservations'])->remember($cacheKey, now()->addMinutes(5), function() {...});
    }
}

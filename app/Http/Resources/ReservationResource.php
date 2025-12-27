<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description,
            'date' => $this->date,
            'heure_debut' => $this->heure_debut,
            'heure_fin' => $this->heure_fin,
            'nombre_personnes' => $this->nombre_personnes,
            'status' => $this->status,
            'room' => new RoomResource($this->whenLoaded('room')),
            'user' => new UserResource($this->whenLoaded('user')),
            'participants' => UserResource::collection($this->whenLoaded('participants')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

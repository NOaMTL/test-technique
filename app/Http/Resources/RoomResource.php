<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
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
            'nom' => $this->nom,
            'capacite' => $this->capacite,
            'etage' => $this->etage,
            'equipement' => $this->equipement,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'constraints' => $this->constraints,
            'constraints_array' => $this->whenLoaded('constraintValidator', function () {
                return app(\App\Services\RoomConstraintValidator::class)
                    ->getConstraintsArray($this->resource);
            }),
            'images' => RoomImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

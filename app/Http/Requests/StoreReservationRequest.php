<?php

namespace App\Http\Requests;

use App\Rules\Reservation\GlobalSettingsRule;
use App\Rules\Reservation\OverlapRule;
use App\Rules\Reservation\RoomConstraintRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isAdmin = $this->user() && $this->user()->role === 'admin';
        
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'date' => $isAdmin
                ? ['required', 'date', new GlobalSettingsRule($isAdmin, 'date')]
                : ['required', 'date', 'after_or_equal:today', new GlobalSettingsRule($isAdmin, 'date')],
            'heure_debut' => [
                'required', 
                'date_format:H:i', 
                new GlobalSettingsRule($isAdmin, 'time_slot'), 
                new OverlapRule(),
                new RoomConstraintRule()
            ],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut', new GlobalSettingsRule($isAdmin, 'time_slot')],
            'titre' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'nombre_personnes' => 'nullable|integer|min:1',
            'participants' => ['nullable', 'array'],
            'participants.*' => 'exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Veuillez sélectionner une salle.',
            'room_id.exists' => 'La salle sélectionnée n\'existe pas.',
            'date.required' => 'Veuillez sélectionner une date.',
            'date.after_or_equal' => 'La date ne peut pas être dans le passé.',
            'heure_debut.required' => 'Veuillez indiquer l\'heure de début.',
            'heure_debut.date_format' => 'Le format de l\'heure de début est invalide.',
            'heure_fin.required' => 'Veuillez indiquer l\'heure de fin.',
            'heure_fin.date_format' => 'Le format de l\'heure de fin est invalide.',
            'heure_fin.after' => 'L\'heure de fin doit être postérieure à l\'heure de début.',
            'participants.array' => 'Le format des participants est invalide.',
            'participants.*.exists' => 'Un ou plusieurs participants sont invalides.',
        ];
    }
}

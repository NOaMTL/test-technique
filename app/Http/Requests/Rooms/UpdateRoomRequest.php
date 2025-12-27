<?php

namespace App\Http\Requests\Rooms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'capacite' => 'required|integer|min:1',
            'etage' => 'required|integer',
            'equipement' => 'nullable|array',
            'equipement.*' => 'string',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'integer|exists:room_images,id',
            'existing_images_order' => 'nullable|array',
            'existing_images_order.*' => 'integer|exists:room_images,id',
            'constraints' => 'nullable|array',
            'constraints.time_period' => 'nullable|in:morning,afternoon,full_day',
            'constraints.days_allowed' => 'nullable|array',
            'constraints.days_allowed.*' => 'integer|min:1|max:7',
            'constraints.advance_booking_days' => 'nullable|integer|min:1',
            'constraints.weekly_hours_quota' => 'nullable|numeric|min:0.5',
            'constraints.daily_booking_limit' => 'nullable|integer|min:1',
            'constraints.min_participants' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la salle est obligatoire.',
            'nom.max' => 'Le nom de la salle ne peut pas dépasser :max caractères.',
            'capacite.required' => 'La capacité de la salle est obligatoire.',
            'capacite.integer' => 'La capacité doit être un nombre entier.',
            'capacite.min' => 'La capacité doit être au moins :min.',
            'etage.required' => 'L\'étage de la salle est obligatoire.',
            'etage.integer' => 'L\'étage doit être un nombre entier.',
            'images.*.image' => 'Chaque fichier doit être une image.',
            'images.*.mimes' => 'Les images doivent être au format: jpeg, png, jpg, gif ou webp.',
            'images.*.max' => 'Chaque image ne peut pas dépasser :max Ko.',
            'delete_images.*.exists' => 'L\'image à supprimer n\'existe pas.',
            'existing_images_order.*.exists' => 'L\'image n\'existe pas.',
            'constraints.time_period.in' => 'La période doit être: matin, après-midi ou journée complète.',
            'constraints.days_allowed.*.integer' => 'Les jours autorisés doivent être des nombres entre 1 et 7.',
        ];
    }
}

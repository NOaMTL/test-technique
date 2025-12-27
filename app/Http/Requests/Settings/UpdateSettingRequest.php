<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
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
            'settings' => 'required|array',
            'settings.*.id' => 'required|exists:settings,id',
            'settings.*.value' => 'required',
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
            'settings.required' => 'Les paramètres sont obligatoires.',
            'settings.array' => 'Les paramètres doivent être un tableau.',
            'settings.*.id.required' => 'L\'identifiant du paramètre est obligatoire.',
            'settings.*.id.exists' => 'Le paramètre n\'existe pas.',
            'settings.*.value.required' => 'La valeur du paramètre est obligatoire.',
        ];
    }
}

<?php

namespace App\Rules\Reservation;

use Closure;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class GlobalSettingsRule implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    public function __construct(
        protected bool $isAdmin,
        protected string $validationType // 'date', 'time_slot'
    ) {}

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $settings = $this->getSettings();

        if ($this->validationType === 'date') {
            $this->validateDate($value, $settings, $fail);
        } elseif ($this->validationType === 'time_slot') {
            $this->validateTimeSlot($value, $settings, $fail);
        }
    }

    protected function getSettings(): Collection
    {
        return cache()->remember('reservation_settings', 3600, function () {
            return Setting::whereIn('key', [
                'reservations.block_weekends',
                'reservations.slot_duration',
                'reservations.min_advance_hours',
                'reservations.max_advance_days',
            ])->get()->keyBy('key');
        });
    }

    protected function validateDate(mixed $value, Collection $settings, Closure $fail): void
    {
        $blockWeekends = $settings->get('reservations.block_weekends')?->typed_value ?? false;
        $minAdvanceHours = $settings->get('reservations.min_advance_hours')?->typed_value ?? 2;
        $maxAdvanceDays = $settings->get('reservations.max_advance_days')?->typed_value ?? 30;

        try {
            $date = Carbon::parse($value);
        } catch (\Exception $e) {
            $fail('Le format de la date est invalide.');
            return;
        }

        // Validation: blocage des weekends
        if ($blockWeekends && $date->isWeekend()) {
            $fail('Les réservations ne sont pas autorisées le weekend.');
        }

        // Validation: délai maximum
        if ($date->gt(Carbon::now()->addDays($maxAdvanceDays))) {
            $fail("Réservation impossible à plus de {$maxAdvanceDays} jours.");
        }

        // Validation: délai minimum (pour non-admins uniquement)
        if (!$this->isAdmin && isset($this->data['heure_debut'])) {
            try {
                $dateTime = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    $date->format('Y-m-d') . ' ' . $this->data['heure_debut']
                );

                if ($dateTime->lt(Carbon::now()->addHours($minAdvanceHours))) {
                    $fail("Réservation au moins {$minAdvanceHours}h à l'avance.");
                }
            } catch (\Exception $e) {
                // L'erreur de format sera gérée par d'autres règles
            }
        }
    }

    protected function validateTimeSlot(mixed $value, Collection $settings, Closure $fail): void
    {
        $slotDuration = $settings->get('reservations.slot_duration')?->typed_value ?? 30;

        try {
            $time = Carbon::createFromFormat('H:i', $value);
        } catch (\Exception $e) {
            $fail('Le format de l\'heure est invalide.');
            return;
        }

        if ($time->minute % $slotDuration !== 0) {
            $fail("Les horaires doivent être en multiples de {$slotDuration} minutes.");
        }
    }
}

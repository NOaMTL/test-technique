<?php

namespace App\Observers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingObserver
{
    /**
     * Handle the Setting "saved" event.
     */
    public function saved(Setting $setting): void
    {
        // Invalider le cache si un setting de réservation est modifié
        if (str_starts_with($setting->key, 'reservations.')) {
            Cache::forget('reservation_settings');
        }
    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Setting $setting): void
    {
        if (str_starts_with($setting->key, 'reservations.')) {
            Cache::forget('reservation_settings');
        }
    }
}

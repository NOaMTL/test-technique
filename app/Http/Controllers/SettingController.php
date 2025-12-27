<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\Cache\CacheInvalidationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function __construct(
        protected CacheInvalidationService $cacheService,
    ) {}

    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get();
        
        // Grouper les settings par groupe
        $grouped = $settings->groupBy('group')->map(function ($items) {
            return $items->map(function ($setting) {
                return [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'title' => $setting->title,
                    'value' => $setting->typed_value,
                    'type' => $setting->type,
                    'description' => $setting->description,
                ];
            });
        });

        return Inertia::render('Settings', [
            'settings' => $grouped,
        ]);
    }

    public function update(UpdateSettingRequest $request)
    {
        foreach ($request->validated()['settings'] as $settingData) {
            $setting = Setting::findOrFail($settingData['id']);
            $setting->setTypedValue($settingData['value']);
            $setting->save();
        }

        return back()->with('success', 'Paramètres mis à jour avec succès');
    }

    /**
     * Clear all application cache
     */
    public function clearCache(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $this->cacheService->clearAll();

        return response()->json([
            'message' => 'Cache général vidé avec succès',
            'success' => true
        ]);
    }

    /**
     * Clear specific cache by type
     */
    public function clearSpecificCache(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:rooms,users,equipments,floors,favorites,reservations'
        ]);

        $this->cacheService->invalidateByType($validated['type']);

        return response()->json([
            'message' => 'Cache "' . $validated['type'] . '" vidé avec succès',
            'success' => true
        ]);
    }
}

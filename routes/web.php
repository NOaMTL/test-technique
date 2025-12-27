<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Route de test pour vérifier l'authentification
    Route::get('/test-auth', function () {
        return response()->json([
            'authenticated' => true,
            'user' => Auth::user(),
        ]);
    });

    // Dashboard - Redirige vers le bon dashboard selon le rôle
    Route::get('dashboard', function () {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        // Si l'utilisateur est admin, afficher le dashboard admin avec toutes les salles et réservations
        if ($user && $user->role === 'admin') {
            return Inertia::render('Dashboard');
        }
        
        // Sinon, afficher le dashboard utilisateur avec seulement ses réservations
        return Inertia::render('UserDashboard');
    })->name('dashboard');

    // Dashboard utilisateur - Mes réservations
    Route::get('my-reservations', function () {
        return Inertia::render('MyReservations');
    })->name('my-reservations');

    // Page des salles avec système de favoris
    Route::get('rooms', function () {
        return Inertia::render('Rooms');
    })->name('rooms');

    // Page de création de réservation
    Route::get('reservations/create', function () {
        $settings = \App\Models\Setting::all()->keyBy('key');
        return Inertia::render('CreateReservation', [
            'settings' => [
                'opening_time' => $settings['reservations.opening_time']->typed_value ?? '08:00',
                'closing_time' => $settings['reservations.closing_time']->typed_value ?? '18:00',
                'slot_duration' => $settings['reservations.slot_duration']->typed_value ?? 30,
                'min_advance_hours' => $settings['reservations.min_advance_hours']->typed_value ?? 2,
                'max_advance_days' => $settings['reservations.max_advance_days']->typed_value ?? 30,
                'block_weekends' => $settings['reservations.block_weekends']->typed_value ?? false,
            ],
        ]);
    })->name('reservations.create');

    // Page de modification de réservation
    Route::get('reservations/{id}/edit', function ($id) {
        $settings = \App\Models\Setting::all()->keyBy('key');
        return Inertia::render('EditReservation', [
            'reservationId' => $id,
            'settings' => [
                'opening_time' => $settings['reservations.opening_time']->typed_value ?? '08:00',
                'closing_time' => $settings['reservations.closing_time']->typed_value ?? '18:00',
                'slot_duration' => $settings['reservations.slot_duration']->typed_value ?? 30,
                'min_advance_hours' => $settings['reservations.min_advance_hours']->typed_value ?? 2,
                'max_advance_days' => $settings['reservations.max_advance_days']->typed_value ?? 30,
                'block_weekends' => $settings['reservations.block_weekends']->typed_value ?? false,
            ],
        ]);
    })->name('reservations.edit');

    // Admin - Toutes les réservations
    Route::get('admin/reservations', function () {
        return Inertia::render('AdminReservations');
    })->name('admin.reservations');

    // Admin - Gestion des utilisateurs
    Route::get('admin/users', function () {
        return Inertia::render('AdminUsers');
    })->name('admin.users');

    // Admin - Gestion des salles
    Route::resource('admin/rooms', \App\Http\Controllers\Admin\RoomController::class)
        ->names('admin.rooms')
        ->except(['show']);
    Route::patch('admin/rooms/{room}/toggle-active', [\App\Http\Controllers\Admin\RoomController::class, 'toggleActive'])
        ->name('admin.rooms.toggle-active');

    // Admin - Paramètres de l'application
    Route::get('admin/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('admin.settings');
    Route::put('admin/settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('admin.settings.update');
});

require __DIR__.'/settings.php';

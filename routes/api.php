<?php

use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Routes publiques (authentification requise via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    
    // Routes pour les salles
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/equipments', [RoomController::class, 'equipments']);
    Route::get('/rooms/floors', [RoomController::class, 'floors']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::post('/rooms/{room}/favorite', [RoomController::class, 'toggleFavorite']);
    Route::get('/favorites', [RoomController::class, 'favorites']);
    Route::post('/rooms/available', [RoomController::class, 'available']);
    
    // Routes pour les utilisateurs
    Route::get('/users/search', [UserController::class, 'search']);
    Route::get('/users/{user}', [UserController::class, 'show']);

    // Routes pour les réservations
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/user', [ReservationController::class, 'userReservations']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
    Route::post('/reservations/check-availability', [ReservationController::class, 'checkAvailability']);

    // Routes admin
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole']);
        Route::post('/cache/clear', [\App\Http\Controllers\SettingController::class, 'clearCache']);
        Route::post('/cache/clear-specific', [\App\Http\Controllers\SettingController::class, 'clearSpecificCache']);
        Route::post('/rooms/{room}/images-order', [\App\Http\Controllers\Admin\RoomController::class, 'updateImagesOrder']);
        Route::delete('/rooms/{room}/images/{image}', [\App\Http\Controllers\Admin\RoomController::class, 'deleteImage']);
    });
});

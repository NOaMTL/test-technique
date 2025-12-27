<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminUserController extends Controller
{
    /**
     * Get all users with their reservations count.
     */
    public function index(Request $request)
    {
        // Vérifier que l'utilisateur est admin
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $users = User::withCount('reservations')
            ->orderBy('role', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return response()->json($users);
    }

    /**
     * Update user role.
     */
    public function updateRole(Request $request, User $user)
    {
        // Vérifier que l'utilisateur est admin
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        // Empêcher de modifier son propre rôle
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier votre propre rôle'], 422);
        }

        $request->validate([
            'role' => 'required|in:user,admin',
        ]);

        $user->update([
            'role' => $request->role,
        ]);

        // Invalider le cache des utilisateurs
        Cache::forget('users.active.all');

        return response()->json($user);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    /**
     * Rechercher des utilisateurs (typeahead).
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        // Cache de tous les utilisateurs actifs (1h)
        $allUsers = Cache::remember('users.active.all', 3600, function() {
            return User::select('id', 'name', 'email')->get();
        });
        
        // Filtrer dans la collection en cache
        $users = $allUsers->filter(function($user) use ($query) {
            return stripos($user->name, $query) !== false || 
                   stripos($user->email, $query) !== false;
        })->take(10)->values();
        
        return response()->json($users);
    }
    
    /**
     * Récupérer un utilisateur par ID.
     */
    public function show(User $user)
    {
        return response()->json($user->only(['id', 'name', 'email']));
    }
}

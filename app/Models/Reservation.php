<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'user_id',
        'date',
        'heure_debut',
        'heure_fin',
        'titre',
        'description',
        'nombre_personnes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected $appends = [];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reservation_participants')
            ->withTimestamps();
    }
}

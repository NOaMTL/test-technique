<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'capacite',
        'etage',
        'equipement',
        'description',
        'is_active',
        'constraints',
    ];

    protected $casts = [
        'equipement' => 'array',
        'is_active' => 'boolean',
        'constraints' => 'array',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_favorite_rooms')
            ->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class)->orderBy('order');
    }
}

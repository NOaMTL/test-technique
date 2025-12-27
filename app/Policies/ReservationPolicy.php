<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    /**
     * Determine if the user can view any reservations.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the reservation.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        return $user->role === 'admin' || $user->id === $reservation->user_id;
    }

    /**
     * Determine if the user can create reservations.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the reservation.
     */
    public function update(User $user, Reservation $reservation): bool
    {
        return $user->role === 'admin' || $user->id === $reservation->user_id;
    }

    /**
     * Determine if the user can delete the reservation.
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->role === 'admin' || $user->id === $reservation->user_id;
    }
}

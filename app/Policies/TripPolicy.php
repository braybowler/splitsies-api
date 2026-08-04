<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    /**
     * Any member of the trip may view it.
     */
    public function view(User $user, Trip $trip): bool
    {
        return $trip->hasMember($user);
    }

    /**
     * Adding and deactivating participants is collaborative — any member may
     * do it. (Owner-only actions are reserved for trip-level destruction.)
     */
    public function manageParticipants(User $user, Trip $trip): bool
    {
        return $trip->hasMember($user);
    }

    /**
     * Only the owner may delete the trip. Defined for the delete-trip endpoint
     * that lands in the invites/ownership slice; no caller yet.
     */
    public function delete(User $user, Trip $trip): bool
    {
        return $trip->isOwnedBy($user);
    }
}

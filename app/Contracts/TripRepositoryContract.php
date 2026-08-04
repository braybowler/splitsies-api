<?php

namespace App\Contracts;

use App\Enums\TripRole;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface TripRepositoryContract
{
    /**
     * Persist a new trip.
     *
     * @param  array{name: string, base_currency: string, owner_id: int, starts_on: ?string, ends_on: ?string}  $attributes
     */
    public function create(array $attributes): Trip;

    /**
     * Add a membership row granting a user access to a trip in a given role.
     */
    public function addMember(int $tripId, int $userId, TripRole $role): TripMember;

    /**
     * All trips a user is a member of, most recent first.
     *
     * @return Collection<int, Trip>
     */
    public function forUser(User $user): Collection;

    /**
     * Eager-load the relations needed to render a trip's detail view.
     */
    public function loadForShow(Trip $trip): Trip;
}

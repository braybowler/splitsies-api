<?php

namespace App\Repositories;

use App\Contracts\TripRepositoryContract;
use App\Enums\TripRole;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TripRepository implements TripRepositoryContract
{
    public function create(array $attributes): Trip
    {
        return Trip::create($attributes);
    }

    public function addMember(int $tripId, int $userId, TripRole $role): TripMember
    {
        return TripMember::create([
            'trip_id' => $tripId,
            'user_id' => $userId,
            'role' => $role,
        ]);
    }

    public function forUser(User $user): Collection
    {
        return Trip::query()
            ->whereHas('members', fn ($query) => $query->where('user_id', $user->id))
            ->with('members.user') // TripResource needs members to derive the user's role
            ->latest()
            ->get();
    }

    public function loadForShow(Trip $trip): Trip
    {
        return $trip->load(['members.user', 'participants']);
    }
}

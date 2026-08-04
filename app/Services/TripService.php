<?php

namespace App\Services;

use App\Contracts\ParticipantRepositoryContract;
use App\Contracts\TripRepositoryContract;
use App\Enums\TripRole;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TripService
{
    public function __construct(
        private TripRepositoryContract $trips,
        private ParticipantRepositoryContract $participants,
    ) {}

    /**
     * Create a trip and seat its creator as both the owner member and a
     * participant. Runs as one transaction — a trip without its owner-member
     * would be unusable.
     *
     * @param  array{starts_on?: ?string, ends_on?: ?string}  $dates
     */
    public function createTrip(User $creator, string $name, string $baseCurrency, string $displayName, array $dates = []): Trip
    {
        return DB::transaction(function () use ($creator, $name, $baseCurrency, $displayName, $dates): Trip {
            $trip = $this->trips->create([
                'name' => $name,
                'base_currency' => $baseCurrency,
                'owner_id' => $creator->id,
                'starts_on' => $dates['starts_on'] ?? null,
                'ends_on' => $dates['ends_on'] ?? null,
            ]);

            $this->trips->addMember($trip->id, $creator->id, TripRole::Owner);
            $this->participants->create($trip->id, $displayName, $creator->id);

            // Trip creation is where a creator without a name finally provides
            // one (the Auth slice deferred name capture); backfill it once.
            if ($creator->name === null) {
                $creator->forceFill(['name' => $displayName])->save();
            }

            return $this->trips->loadForShow($trip);
        });
    }

    /**
     * @return Collection<int, Trip>
     */
    public function listForUser(User $user): Collection
    {
        return $this->trips->forUser($user);
    }

    public function show(Trip $trip): Trip
    {
        return $this->trips->loadForShow($trip);
    }
}

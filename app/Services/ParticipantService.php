<?php

namespace App\Services;

use App\Contracts\ParticipantRepositoryContract;
use App\Models\Participant;
use App\Models\Trip;

class ParticipantService
{
    public function __construct(
        private ParticipantRepositoryContract $participants,
    ) {}

    /**
     * Add a ghost participant (a name with no account) to a trip.
     */
    public function addGhost(Trip $trip, string $name): Participant
    {
        return $this->participants->create($trip->id, $name);
    }

    /**
     * Remove a participant by deactivating it, preserving any history it holds.
     */
    public function deactivate(Participant $participant): Participant
    {
        return $this->participants->deactivate($participant);
    }
}

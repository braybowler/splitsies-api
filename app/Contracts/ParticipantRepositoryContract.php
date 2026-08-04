<?php

namespace App\Contracts;

use App\Models\Participant;

interface ParticipantRepositoryContract
{
    /**
     * Create a participant in a trip. A null userId is a ghost.
     */
    public function create(int $tripId, string $name, ?int $userId = null): Participant;

    /**
     * Mark a participant inactive — excluded from new expenses, retained in
     * history. Returns the refreshed model.
     */
    public function deactivate(Participant $participant): Participant;
}

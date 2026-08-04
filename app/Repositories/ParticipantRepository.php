<?php

namespace App\Repositories;

use App\Contracts\ParticipantRepositoryContract;
use App\Models\Participant;

class ParticipantRepository implements ParticipantRepositoryContract
{
    public function create(int $tripId, string $name, ?int $userId = null): Participant
    {
        return Participant::create([
            'trip_id' => $tripId,
            'name' => $name,
            'user_id' => $userId,
            'active' => true,
        ]);
    }

    public function deactivate(Participant $participant): Participant
    {
        $participant->update(['active' => false]);

        return $participant->refresh();
    }
}

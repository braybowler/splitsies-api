<?php

namespace App\Http\Controllers\Participants;

use App\Http\Controllers\Controller;
use App\Http\Resources\ParticipantResource;
use App\Models\Participant;
use App\Models\Trip;
use App\Services\ParticipantService;
use Illuminate\Support\Facades\Gate;

class DeactivateParticipantController extends Controller
{
    public function __construct(
        private ParticipantService $participants,
    ) {}

    public function __invoke(Trip $trip, Participant $participant): ParticipantResource
    {
        Gate::authorize('manageParticipants', $trip);

        return ParticipantResource::make(
            $this->participants->deactivate($participant),
        );
    }
}

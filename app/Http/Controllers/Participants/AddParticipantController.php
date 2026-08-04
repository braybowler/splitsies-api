<?php

namespace App\Http\Controllers\Participants;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participants\AddParticipantRequest;
use App\Http\Resources\ParticipantResource;
use App\Models\Trip;
use App\Services\ParticipantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AddParticipantController extends Controller
{
    public function __construct(
        private ParticipantService $participants,
    ) {}

    public function __invoke(AddParticipantRequest $request, Trip $trip): JsonResponse
    {
        Gate::authorize('manageParticipants', $trip);

        $participant = $this->participants->addGhost(
            $trip,
            $request->string('name')->toString(),
        );

        return ParticipantResource::make($participant)
            ->response()
            ->setStatusCode(201);
    }
}

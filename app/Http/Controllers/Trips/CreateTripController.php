<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trips\CreateTripRequest;
use App\Http\Resources\TripResource;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;

class CreateTripController extends Controller
{
    public function __construct(
        private TripService $trips,
    ) {}

    public function __invoke(CreateTripRequest $request): JsonResponse
    {
        $trip = $this->trips->createTrip(
            $request->user(),
            $request->string('name')->toString(),
            $request->string('base_currency')->toString(),
            $request->displayName(),
            $request->only(['starts_on', 'ends_on']),
        );

        return TripResource::make($trip)
            ->response()
            ->setStatusCode(201);
    }
}

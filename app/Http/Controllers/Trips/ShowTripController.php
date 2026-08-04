<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Services\TripService;
use Illuminate\Support\Facades\Gate;

class ShowTripController extends Controller
{
    public function __construct(
        private TripService $trips,
    ) {}

    public function __invoke(Trip $trip): TripResource
    {
        Gate::authorize('view', $trip);

        return TripResource::make($this->trips->show($trip));
    }
}

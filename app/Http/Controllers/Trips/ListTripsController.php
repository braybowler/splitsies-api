<?php

namespace App\Http\Controllers\Trips;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Services\TripService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListTripsController extends Controller
{
    public function __construct(
        private TripService $trips,
    ) {}

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        return TripResource::collection(
            $this->trips->listForUser($request->user()),
        );
    }
}

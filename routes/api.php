<?php

use App\Http\Controllers\LogoutController;
use App\Http\Controllers\Participants\AddParticipantController;
use App\Http\Controllers\Participants\DeactivateParticipantController;
use App\Http\Controllers\RedeemMagicLinkController;
use App\Http\Controllers\RequestMagicLinkController;
use App\Http\Controllers\Trips\CreateTripController;
use App\Http\Controllers\Trips\ListTripsController;
use App\Http\Controllers\Trips\ShowTripController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/magic-link', RequestMagicLinkController::class)
    ->middleware('throttle:magic-link');

Route::post('/auth/redeem', RedeemMagicLinkController::class)
    ->middleware('throttle:30,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/auth/logout', LogoutController::class);

    Route::get('/trips', ListTripsController::class);
    Route::post('/trips', CreateTripController::class);
    Route::get('/trips/{trip}', ShowTripController::class);

    // Scoped bindings: {participant} must belong to {trip}, else 404.
    Route::scopeBindings()->group(function () {
        Route::post('/trips/{trip}/participants', AddParticipantController::class);
        Route::delete('/trips/{trip}/participants/{participant}', DeactivateParticipantController::class);
    });
});

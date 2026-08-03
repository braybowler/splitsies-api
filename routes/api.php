<?php

use App\Http\Controllers\RedeemMagicLinkController;
use App\Http\Controllers\RequestMagicLinkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/magic-link', RequestMagicLinkController::class)
    ->middleware('throttle:magic-link');

Route::post('/auth/redeem', RedeemMagicLinkController::class)
    ->middleware('throttle:30,1');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

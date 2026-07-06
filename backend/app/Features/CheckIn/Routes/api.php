<?php

use Illuminate\Support\Facades\Route;
use App\Features\CheckIn\Controllers\CheckInController;
use App\Http\Controllers\Venue\CheckInController as VenueCheckInController;

// Check-in routes (venue staff)
Route::middleware('auth:sanctum')->prefix('venue')->group(function () {
    Route::post('/check-in', [VenueCheckInController::class, 'store']);
    Route::get('/check-ins', [CheckInController::class, 'index']);
});

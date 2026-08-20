<?php

use Illuminate\Support\Facades\Route;
use App\Features\QRCodeTicketing\Controllers\QRGenerationController;
use App\Features\QRCodeTicketing\Controllers\QRVerificationController;
use App\Features\QRCodeTicketing\Controllers\VenueCheckInController;
use App\Features\QRCodeTicketing\Controllers\CheckInAnalyticsController;

// QR generation routes (organizer)
Route::middleware('auth:sanctum')->prefix('organizer')->group(function () {
    Route::post('/events/{event}/tickets/{ticket}/qr', [QRGenerationController::class, 'generate']);
});

// QR verification and check-in routes (venue staff)
Route::middleware('auth:sanctum')->prefix('venue')->group(function () {
    Route::post('/check-in/qr', [QRVerificationController::class, 'verify']);
    Route::post('/check-in/manual', [VenueCheckInController::class, 'manualCheckIn']);
    Route::get('/events/{event}/check-ins', [CheckInAnalyticsController::class, 'index']);
});

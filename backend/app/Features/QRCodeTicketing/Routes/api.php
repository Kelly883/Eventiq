<?php

use Illuminate\Support\Facades\Route;
use App\Features\QRCodeTicketing\Controllers\QRGenerationController;
use App\Features\QRCodeTicketing\Controllers\QRVerificationController;
use App\Features\QRCodeTicketing\Controllers\VenueCheckInController;
use App\Features\QRCodeTicketing\Controllers\CheckInAnalyticsController;
use App\Features\QRCodeTicketing\Controllers\TicketCheckInController;
use App\Features\QRCodeTicketing\Controllers\UserQRGenerationController;

// QR generation routes (organizer)
Route::middleware('bearer')->prefix('organizer')->group(function () {
    Route::post('/events/{event}/tickets/{ticket}/qr', [QRGenerationController::class, 'generate']);
});

// QR verification and check-in routes (venue staff)
Route::middleware('bearer')->prefix('venue')->group(function () {
    Route::post('/check-in/qr', [QRVerificationController::class, 'verify']);
    Route::post('/check-in/manual', [VenueCheckInController::class, 'manualCheckIn']);
    Route::post('/check-in/bulk', [TicketCheckInController::class, 'bulkCheckIn']);
    Route::post('/check-in/detect-duplicate', [TicketCheckInController::class, 'detectDuplicate']);
    Route::post('/check-in/offline-sync', [TicketCheckInController::class, 'offlineSync']);
    Route::get('/check-in/sync', [TicketCheckInController::class, 'syncCheckIns']);
    Route::get('/events/{event}/check-ins', [CheckInAnalyticsController::class, 'index']);
});

// User-facing QR generation
Route::middleware('bearer')->prefix('tickets')->group(function () {
    Route::post('/generate-qr', [UserQRGenerationController::class, 'generate']);
    Route::post('/verify-qr', [QRVerificationController::class, 'verifyForUser']);
    Route::get('/{ticketId}/check-in', [TicketCheckInController::class, 'checkIn'])->middleware('throttle:qr-check-in');
    Route::post('/{ticketId}/check-in', [TicketCheckInController::class, 'checkIn'])->middleware('throttle:qr-check-in');
    Route::post('/{ticketId}/void', [TicketCheckInController::class, 'void'])->middleware('throttle:qr-void');
});

<?php

use Illuminate\Support\Facades\Route;
use App\Features\Payment\Controllers\PaystackController;
use App\Features\Payment\Controllers\FlutterwaveController;
use App\Features\Payment\Http\Controllers\OrganizerPayoutMethodController;

// Payment gateway-agnostic API routes (scaffolding per prompt).
// Business logic endpoints (initialize/verify) will be added in
// subsequent steps. Webhooks are unauthenticated by nature (called by
// the payment provider, not a logged-in user) - signature verification
// inside each controller is what protects them.

Route::post('/payments/paystack/webhook', [PaystackController::class, 'webhook']);
Route::post('/payments/flutterwave/webhook', [FlutterwaveController::class, 'webhook']);

Route::middleware('auth:sanctum')->prefix('organizer/payout-methods')->group(function () {
    Route::get('/', [OrganizerPayoutMethodController::class, 'index']);
    Route::post('/', [OrganizerPayoutMethodController::class, 'store']);
    Route::delete('/{id}', [OrganizerPayoutMethodController::class, 'destroy']);
});


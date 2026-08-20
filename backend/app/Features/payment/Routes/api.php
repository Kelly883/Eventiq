<?php

use Illuminate\Support\Facades\Route;
use App\Features\Payment\Controllers\PaystackController;
use App\Features\Payment\Controllers\FlutterwaveController;
use App\Features\Payment\Controllers\PaystackInitializeController;
use App\Features\Payment\Controllers\PaystackVerifyController;
use App\Features\Payment\Controllers\FlutterwaveInitializeController;
use App\Features\Payment\Controllers\FlutterwaveVerifyController;
use App\Features\Payment\Http\Controllers\OrganizerPayoutMethodController;

// Payment gateway endpoints.
// Webhooks are unauthenticated by nature (called by the payment provider).
// Initialize/verify endpoints are used by the frontend checkout flow.

Route::post('/payments/paystack/webhook', [PaystackController::class, 'webhook']);
Route::post('/payments/flutterwave/webhook', [FlutterwaveController::class, 'webhook']);

Route::post('/payments/paystack/initialize', [PaystackInitializeController::class, '__invoke']);
Route::post('/payments/paystack/verify', [PaystackVerifyController::class, '__invoke']);

Route::post('/payments/flutterwave/initialize', [FlutterwaveInitializeController::class, '__invoke']);
Route::post('/payments/flutterwave/verify', [FlutterwaveVerifyController::class, '__invoke']);

Route::middleware('auth:sanctum')->prefix('organizer/payout-methods')->group(function () {
    Route::get('/', [OrganizerPayoutMethodController::class, 'index']);
    Route::post('/', [OrganizerPayoutMethodController::class, 'store']);
    Route::delete('/{id}', [OrganizerPayoutMethodController::class, 'destroy']);
});


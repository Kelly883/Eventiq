<?php

use Illuminate\Support\Facades\Route;
use App\Features\Payment\Controllers\PaystackController;
use App\Features\Payment\Controllers\FlutterwaveController;
use App\Features\Payment\Controllers\PaystackInitializeController;
use App\Features\Payment\Controllers\PaystackVerifyController;
use App\Features\Payment\Controllers\FlutterwaveInitializeController;
use App\Features\Payment\Controllers\FlutterwaveVerifyController;
use App\Features\Payment\Http\Controllers\OrganizerPayoutMethodController;
use App\Features\Payment\Controllers\PaymentMethodController;
use App\Features\Payment\Controllers\OrganizerPaymentSettingsController;
use App\Features\Payment\Controllers\TransactionController;

// Payment gateway endpoints.
// Webhooks are unauthenticated by nature (called by the payment provider).
// Initialize/verify endpoints are used by the frontend checkout flow.

Route::post('/payments/paystack/webhook', [PaystackController::class, 'webhook']);
Route::post('/payments/flutterwave/webhook', [FlutterwaveController::class, 'webhook']);

Route::post('/payments/paystack/initialize', [PaystackInitializeController::class, '__invoke']);
Route::post('/payments/paystack/verify', [PaystackVerifyController::class, '__invoke']);

Route::post('/payments/flutterwave/initialize', [FlutterwaveInitializeController::class, '__invoke']);
Route::post('/payments/flutterwave/verify', [FlutterwaveVerifyController::class, '__invoke']);

// Organizer payout methods (auth + organizer role enforced)
Route::middleware(['auth:sanctum', 'role:organizer'])->prefix('organizer/payout-methods')->group(function () {
    Route::get('/', [OrganizerPayoutMethodController::class, 'index']);
    Route::post('/', [OrganizerPayoutMethodController::class, 'store']);
    Route::delete('/{id}', [OrganizerPayoutMethodController::class, 'destroy']);
});

// User payment methods
Route::middleware('auth:sanctum')->prefix('user/payment-methods')->group(function () {
    Route::get('/', [PaymentMethodController::class, 'index']);
    Route::post('/', [PaymentMethodController::class, 'store']);
    Route::get('/{id}', [PaymentMethodController::class, 'show']);
    Route::put('/{id}', [PaymentMethodController::class, 'update']);
    Route::delete('/{id}', [PaymentMethodController::class, 'destroy']);
    Route::post('/{id}/set-default', [PaymentMethodController::class, 'setDefault']);
});

// Organizer payment settings (auth + organizer role enforced).
// Read-only: gateway subaccount/recipient config + connect status are managed
// through onboarding, not by the organizer. No writable endpoint is exposed.
Route::middleware(['auth:sanctum', 'role:organizer'])->prefix('organizer/payment-settings')->group(function () {
    Route::get('/', [OrganizerPaymentSettingsController::class, 'index']);
});

// User transaction history
Route::middleware('auth:sanctum')->prefix('user/transactions')->group(function () {
    Route::get('/', [TransactionController::class, 'history']);
    Route::get('/{id}', [TransactionController::class, 'show']);
});

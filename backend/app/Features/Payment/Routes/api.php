<?php

use Illuminate\Support\Facades\Route;
use App\Features\Payment\Controllers\PaystackController;
use App\Features\Payment\Controllers\FlutterwaveController;

// Payment gateway-agnostic API routes (scaffolding per prompt).
// Business logic endpoints (initialize/verify) will be added in
// subsequent steps. Webhooks are unauthenticated by nature (called by
// the payment provider, not a logged-in user) - signature verification
// inside each controller is what protects them.

Route::post('/payments/paystack/webhook', [PaystackController::class, 'webhook']);
Route::post('/payments/flutterwave/webhook', [FlutterwaveController::class, 'webhook']);


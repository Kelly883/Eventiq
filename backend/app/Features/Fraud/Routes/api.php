<?php

use App\Features\Fraud\Http\Controllers\FraudController;
use Illuminate\Support\Facades\Route;

// Fraud tooling exposes payment-gateway transaction lookups and cross-user
// velocity data — restricted to admins.
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('fraud')->group(function () {
    Route::post('/detect', [FraudController::class, 'detect']);
    Route::get('/transactions/paystack/{reference}', [FraudController::class, 'verifyPaystack']);
    Route::get('/transactions/flutterwave/{transactionId}', [FraudController::class, 'verifyFlutterwave']);
    Route::post('/velocity', [FraudController::class, 'velocity']);
    Route::post('/duplicate-tickets', [FraudController::class, 'duplicateTickets']);
    Route::post('/device', [FraudController::class, 'deviceFingerprint']);
    Route::post('/ip', [FraudController::class, 'ipReputation']);
    Route::get('/event/{id}', [FraudController::class, 'eventDetails']);
});

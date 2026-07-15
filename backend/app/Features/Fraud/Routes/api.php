<?php

use App\Features\Fraud\Http\Controllers\FraudController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('fraud')->group(function () {
    Route::post('/detect', [FraudController::class, 'detect']);
    Route::get('/transactions/paystack/{reference}', [FraudController::class, 'verifyPaystack']);
    Route::get('/transactions/flutterwave/{transactionId}', [FraudController::class, 'verifyFlutterwave']);
    Route::post('/velocity', [FraudController::class, 'velocity']);
    Route::post('/duplicate-tickets', [FraudController::class, 'duplicateTickets']);
    Route::post('/device', [FraudController::class, 'deviceFingerprint']);
    Route::post('/ip', [FraudController::class, 'ipReputation']);
    Route::get('/event/{id}', [FraudController::class, 'eventDetails']);
});

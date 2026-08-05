<?php

use Illuminate\Support\Facades\Route;
use App\Features\Payouts\Controllers\OrganizerPayoutController;
use App\Features\Payouts\Controllers\AdminSettlementController;

// Organizer routes
Route::middleware('auth:sanctum')->prefix('organizer')->group(function () {
    Route::get('/payouts', [OrganizerPayoutController::class, 'index']);
    Route::get('/payouts/summary', [OrganizerPayoutController::class, 'summary']);
    Route::get('/payouts/{payout}', [OrganizerPayoutController::class, 'show']);
    Route::get('/payouts/{payout}/calculation', [OrganizerPayoutController::class, 'calculation']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/settlements', [AdminSettlementController::class, 'index']);
    Route::get('/settlements/summary', [AdminSettlementController::class, 'summary']);
    Route::get('/settlements/export', [AdminSettlementController::class, 'export']);
    Route::post('/settlements', [AdminSettlementController::class, 'store']);
    Route::get('/settlements/{payout}', [AdminSettlementController::class, 'show']);
    Route::post('/settlements/{payout}/process', [AdminSettlementController::class, 'processPayout']);
    Route::post('/settlements/{payout}/fail', [AdminSettlementController::class, 'failPayout']);

    Route::get('/settlement-policies', [AdminSettlementController::class, 'settlementPolicies']);
    Route::post('/settlement-policies', [AdminSettlementController::class, 'storeSettlementPolicy']);
    Route::put('/settlement-policies/{policy}', [AdminSettlementController::class, 'updateSettlementPolicy']);
});

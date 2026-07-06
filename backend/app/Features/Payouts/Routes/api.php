<?php

use Illuminate\Support\Facades\Route;
use App\Features\Payouts\Controllers\OrganizerPayoutController;
use App\Features\Payouts\Controllers\AdminSettlementController;

// Organizer routes
Route::middleware('auth:sanctum')->prefix('organizer')->group(function () {
    Route::get('/payouts', [OrganizerPayoutController::class, 'index']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/settlements', [AdminSettlementController::class, 'index']);
});

<?php

use Illuminate\Support\Facades\Route;
use App\Features\Analytics\Controllers\AnalyticsController;

// Organizer routes for analytics
Route::middleware('auth:sanctum')->prefix('organizer')->group(function () {
    Route::get('/events/{event}/analytics/summary', [AnalyticsController::class, 'getSummary']);
    Route::get('/events/{event}/analytics/sales-velocity', [AnalyticsController::class, 'getSalesVelocity']);
    Route::get('/events/{event}/analytics/detailed', [AnalyticsController::class, 'getDetailed']);
    Route::get('/analytics/comparison', [AnalyticsController::class, 'getComparison']);
});

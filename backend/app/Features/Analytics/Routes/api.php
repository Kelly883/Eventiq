<?php

use Illuminate\Support\Facades\Route;
use App\Features\Analytics\Controllers\AnalyticsController;

// Organizer routes for analytics
Route::middleware('bearer')->prefix('organizer')->group(function () {
    Route::get('/events/{event}/analytics/summary', [AnalyticsController::class, 'getSummary'])
        ->middleware('throttle:analytics-summary');
    Route::get('/events/{event}/analytics/sales-velocity', [AnalyticsController::class, 'getSalesVelocity'])
        ->middleware('throttle:analytics-sales-velocity');
    Route::get('/events/{event}/analytics/detailed', [AnalyticsController::class, 'getDetailed'])
        ->middleware('throttle:analytics-detailed');
    Route::get('/analytics/comparison', [AnalyticsController::class, 'getComparison'])
        ->middleware('throttle:analytics-comparison');
});

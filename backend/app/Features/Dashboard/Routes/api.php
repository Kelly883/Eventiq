<?php

use Illuminate\Support\Facades\Route;
use App\Features\Dashboard\Controllers\DashboardController;

Route::middleware('bearer')->prefix('organizer')->group(function () {
    // Dashboard metrics - 20/min per user
    Route::get('/dashboard/metrics', [DashboardController::class, 'getMetrics'])
        ->middleware('throttle:dashboard-metrics');

    // Dashboard preferences - 20/min per user
    Route::get('/dashboard/preferences', [DashboardController::class, 'getPreferences'])
        ->middleware('throttle:dashboard-preferences');
    Route::put('/dashboard/preferences', [DashboardController::class, 'updatePreferences'])
        ->middleware('throttle:dashboard-preferences');

    // Activity feed - 20/min per user
    Route::get('/dashboard/activity-feed', [DashboardController::class, 'getActivityFeed'])
        ->middleware('throttle:dashboard-activity');
});

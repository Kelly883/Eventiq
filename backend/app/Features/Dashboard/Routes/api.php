<?php

use Illuminate\Support\Facades\Route;
use App\Features\Dashboard\Controllers\DashboardController;

Route::middleware('bearer')->prefix('organizer')->group(function () {
    // Dashboard overview with trends — 20/min per user
    Route::get('/dashboard/overview', [DashboardController::class, 'overview'])
        ->middleware('throttle:dashboard-metrics');

    // Legacy alias for backward compatibility
    Route::get('/dashboard/metrics', [DashboardController::class, 'getMetrics'])
        ->middleware('throttle:dashboard-metrics');

    // Paginated events list — 20/min per user
    Route::get('/dashboard/events', [DashboardController::class, 'events'])
        ->middleware('throttle:dashboard-metrics');

    // Event detail with tier breakdown — 20/min per user
    Route::get('/dashboard/events/{eventId}', [DashboardController::class, 'eventDetail'])
        ->middleware('throttle:dashboard-metrics');

    // Preferences — 20/min per user
    Route::get('/dashboard/preferences', [DashboardController::class, 'getPreferences'])
        ->middleware('throttle:dashboard-preferences');
    Route::match(['put', 'patch'], '/dashboard/preferences', [DashboardController::class, 'updatePreferences'])
        ->middleware('throttle:dashboard-preferences');

    // Activity feed — 20/min per user
    Route::get('/dashboard/activity-feed', [DashboardController::class, 'getActivityFeed'])
        ->middleware('throttle:dashboard-activity');
});

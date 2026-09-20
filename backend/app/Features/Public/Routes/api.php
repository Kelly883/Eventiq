<?php

use Illuminate\Support\Facades\Route;
use App\Features\Public\Controllers\EventController;

// Public event discovery endpoints — no auth required, rate-limited
// to 30/min per IP via the 'discovery' limiter (AppServiceProvider).
Route::middleware('throttle:discovery')->prefix('public')->group(function () {
    // Primary endpoints
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/search', [EventController::class, 'search']);
    Route::get('/events/filters', [EventController::class, 'filters']);
    Route::get('/events/category/{category}', [EventController::class, 'byCategory']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::get('/events/{event}/pricing', [EventController::class, 'pricing']);
    Route::get('/events/{event}/related', [EventController::class, 'related']);

    // Legacy endpoints (kept for backward compatibility)
    Route::get('/categories', [EventController::class, 'categories']);
    Route::get('/events/{event}/ticket-tiers', [EventController::class, 'ticketTiers']);
    Route::get('/events/{event}/pricing-windows', [EventController::class, 'pricingWindows']);
    Route::get('/events/{event}/analytics', [EventController::class, 'analytics']);
    Route::get('/events/{event}/availability', [EventController::class, 'availability']);
});

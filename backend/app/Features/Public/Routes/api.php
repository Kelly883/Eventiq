<?php

use Illuminate\Support\Facades\Route;
use App\Features\Public\Controllers\EventController;

// Public event discovery endpoints — no auth required, rate-limited
// to 30/min per IP via the 'discovery' limiter (AppServiceProvider).
Route::middleware('throttle:discovery')->prefix('public')->group(function () {
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::get('/categories', [EventController::class, 'categories']);
    Route::get('/events/{event}/ticket-tiers', [EventController::class, 'ticketTiers']);
    Route::get('/events/{event}/pricing-windows', [EventController::class, 'pricingWindows']);
    Route::get('/events/{event}/analytics', [EventController::class, 'analytics']);
    Route::get('/events/{event}/availability', [EventController::class, 'availability']);
});

<?php

use App\Features\OrganizerProfile\Controllers\OrganizerProfileController;
use Illuminate\Support\Facades\Route;

// Protected organizer profile routes — spec URLs (bearer handles Sanctum + Bearer token)
// Must be BEFORE the {id} wildcard so /me is not captured as {id}=me
Route::middleware('bearer')->group(function () {
    Route::get('/organizers/me', [OrganizerProfileController::class, 'me']);
    Route::patch('/organizers/me/update', [OrganizerProfileController::class, 'update'])->middleware('throttle:organizer-update');
    Route::post('/organizers/me/upload-avatar', [OrganizerProfileController::class, 'uploadAvatar'])->middleware('throttle:organizer-avatar');
});

// Public organizer profile — 20/min per IP, supports optional auth for private owner view
// Explicitly exclude "me" so /organizers/me is not captured as {id}=me (defense in depth, order already fixes)
Route::middleware(['bearer.optional', 'throttle:organizer-public'])->group(function () {
    Route::get('/organizers/{id}', [OrganizerProfileController::class, 'show'])->where('id', '^(?!me$)[^/]+');
});

Route::middleware(['bearer.optional', 'throttle:organizer-public-events'])->group(function () {
    Route::get('/organizers/{id}/events', [OrganizerProfileController::class, 'events'])->where('id', '^(?!me$)[^/]+');
});

// Legacy singular routes for backward compat (frontend still uses /organizer/profile)
// Support both bearer (custom token) and sanctum for audit-log
Route::middleware('bearer')->group(function () {
    Route::get('/organizer/profile/audit-log', [OrganizerProfileController::class, 'auditLog']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('organizer')->group(function () {
        Route::get('/profile', [OrganizerProfileController::class, 'edit']);
        Route::put('/profile', [OrganizerProfileController::class, 'update']);
    });
});

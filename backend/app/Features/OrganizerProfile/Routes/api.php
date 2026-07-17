<?php

use App\Features\OrganizerProfile\Controllers\OrganizerProfileController;
use Illuminate\Support\Facades\Route;

// Public organizer profile
Route::get('/organizers/{id}', [OrganizerProfileController::class, 'show']);
Route::get('/organizers/{id}/events', [OrganizerProfileController::class, 'events']);

// Protected organizer profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('organizer')->group(function () {
        Route::get('/profile', [OrganizerProfileController::class, 'edit']);
        Route::put('/profile', [OrganizerProfileController::class, 'update']);
        Route::get('/profile/audit-log', [OrganizerProfileController::class, 'auditLog']);
    });
});
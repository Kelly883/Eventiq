<?php

use Illuminate\Support\Facades\Route;

// Placeholder web routes file (needed by Laravel's default bootstrap).
// This project primarily exposes API routes.

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});


// Public API integration routes are exposed without Laravel's default /api prefix.
Route::middleware(['api.key', 'throttle:api-keys'])->prefix('v1')->group(function () {
    Route::get('/events', function (\Illuminate\Http\Request $request) {
        abort_unless(in_array('events:read', $request->attributes->get('api_key_scopes', []), true), 403);

        return \App\Models\Event::query()
            ->where('organizer_id', $request->attributes->get('organizer')->id)
            ->latest()
            ->get();
    });
});

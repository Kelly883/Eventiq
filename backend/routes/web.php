<?php

use Illuminate\Support\Facades\Route;

// Placeholder web routes file (needed by Laravel's default bootstrap).
// This project primarily exposes API routes.

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});


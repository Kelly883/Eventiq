<?php

use App\Features\Accessibility\Http\Controllers\AccessibilityPreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('bearer')->group(function () {
    Route::middleware('throttle:user-preference-read')->get('/users/me/accessibility-preferences', [AccessibilityPreferenceController::class, 'show']);
    Route::middleware('throttle:accessibility-preference-update')->patch('/users/me/accessibility-preferences/update', [AccessibilityPreferenceController::class, 'update']);
});
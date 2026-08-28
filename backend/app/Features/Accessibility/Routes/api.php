<?php

use App\Features\Accessibility\Http\Controllers\AccessibilityPreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users/me/accessibility-preferences', [AccessibilityPreferenceController::class, 'show']);
    Route::patch('/users/me/accessibility-preferences/update', [AccessibilityPreferenceController::class, 'update']);
});
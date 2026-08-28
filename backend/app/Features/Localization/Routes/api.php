<?php

use App\Features\Localization\Http\Controllers\LanguagePreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users/me/language-preferences', [LanguagePreferenceController::class, 'show']);
    Route::patch('/users/me/language-preferences/update', [LanguagePreferenceController::class, 'update']);
});
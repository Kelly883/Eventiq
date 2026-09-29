<?php

use App\Features\Localization\Http\Controllers\LanguagePreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('bearer')->group(function () {
    Route::middleware('throttle:user-preference-read')->get('/users/me/language-preferences', [LanguagePreferenceController::class, 'show']);
    Route::middleware('throttle:language-preference-update')->patch('/users/me/language-preferences/update', [LanguagePreferenceController::class, 'update']);
});
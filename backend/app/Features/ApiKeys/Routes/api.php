<?php

use App\Features\ApiKeys\Controllers\ApiKeyController;
use Illuminate\Support\Facades\Route;

Route::middleware('bearer')->prefix('organizer/api-keys')->group(function () {
    Route::get('/', [ApiKeyController::class, 'index']);
    Route::post('/', [ApiKeyController::class, 'store']);
    Route::delete('/{id}', [ApiKeyController::class, 'destroy']);
});

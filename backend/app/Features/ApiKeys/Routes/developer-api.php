<?php

use App\Features\ApiKeys\Controllers\ApiKeyController;
use App\Http\Controllers\Developer\ApiLogController;
use App\Http\Controllers\Developer\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['bearer', 'role:organizer', 'throttle:developer-portal'])->prefix('developer')->group(function () {
    Route::get('/api-keys', [ApiKeyController::class, 'index']);
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->middleware('throttle:developer-portal-create');
    Route::delete('/api-keys/{keyId}', [ApiKeyController::class, 'destroy'])->middleware('throttle:developer-portal-delete');

    Route::get('/webhooks', [WebhookController::class, 'index']);
    Route::post('/webhooks', [WebhookController::class, 'store'])->middleware('throttle:developer-portal-create');
    Route::delete('/webhooks/{id}', [WebhookController::class, 'destroy'])->middleware('throttle:developer-portal-delete');

    Route::get('/api-logs', [ApiLogController::class, 'index']);
});

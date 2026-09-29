<?php

use App\Http\Controllers\Developer\ApiKeyController;
use App\Http\Controllers\Developer\ApiLogController;
use App\Http\Controllers\Developer\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['bearer', 'role:organizer'])->prefix('developer')->group(function () {
    Route::get('/api-keys', [ApiKeyController::class, 'index']);
    Route::post('/api-keys', [ApiKeyController::class, 'store']);
    Route::delete('/api-keys/{keyId}', [ApiKeyController::class, 'destroy']);

    Route::get('/webhooks', [WebhookController::class, 'index']);
    Route::post('/webhooks', [WebhookController::class, 'store']);
    Route::delete('/webhooks/{id}', [WebhookController::class, 'destroy']);

    Route::get('/api-logs', [ApiLogController::class, 'index']);
});

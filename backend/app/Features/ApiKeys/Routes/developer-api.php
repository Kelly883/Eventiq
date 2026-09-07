<?php

use App\Features\ApiKeys\Http\Controllers\DeveloperController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:organizer'])->prefix('developer')->group(function () {
    Route::get('/api-keys', [DeveloperController::class, 'listApiKeys']);
    Route::post('/api-keys', [DeveloperController::class, 'createApiKey']);
    Route::delete('/api-keys/{keyId}', [DeveloperController::class, 'deleteApiKey']);
    Route::get('/webhooks', [DeveloperController::class, 'listWebhooks']);
    Route::post('/webhooks', [DeveloperController::class, 'createWebhook']);
    Route::delete('/webhooks/{id}', [DeveloperController::class, 'deleteWebhook']);
    Route::get('/api-logs', [DeveloperController::class, 'listApiLogs']);
});
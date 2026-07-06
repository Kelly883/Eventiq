<?php

use Illuminate\Support\Facades\Route;
use App\Features\PushNotifications\Controllers\DeviceTokenController;
use App\Features\PushNotifications\Controllers\AdminPushTemplateController;

// Device token routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens/{token}', [DeviceTokenController::class, 'destroy']);
});

// Admin push template routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('push-templates', AdminPushTemplateController::class);
});

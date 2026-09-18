<?php

use Illuminate\Support\Facades\Route;
use App\Features\PushNotifications\Controllers\DeviceTokenController;
use App\Features\PushNotifications\Controllers\AdminPushTemplateController;
use App\Features\PushNotifications\Controllers\PushNotificationController;

// Device token routes
Route::middleware('bearer')->group(function () {
    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens/{token}', [DeviceTokenController::class, 'destroy']);
    Route::get('/push-notifications/preferences', [PushNotificationController::class, 'preferences']);
    Route::put('/push-notifications/preferences', [PushNotificationController::class, 'updatePreferences']);
    Route::get('/push-notifications/templates', [PushNotificationController::class, 'templates']);
    Route::post('/push-notifications/test', [PushNotificationController::class, 'test']);
});

// Admin push template routes
Route::middleware(['bearer', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('push-templates', AdminPushTemplateController::class);
});

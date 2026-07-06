<?php

use Illuminate\Support\Facades\Route;
use App\Features\EmailNotifications\Controllers\EmailTemplateController;

// Admin email template routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('email-templates', EmailTemplateController::class);
});

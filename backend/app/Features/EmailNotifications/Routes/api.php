<?php

use App\Features\EmailNotifications\Controllers\EmailTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['bearer', 'role:admin', 'throttle:email-templates-list'])->prefix('admin/email-templates')->group(function () {
    Route::get('/', [EmailTemplateController::class, 'index']);
    Route::get('/{template}', [EmailTemplateController::class, 'show']);
    Route::post('/', [EmailTemplateController::class, 'store']);
    Route::patch('/{template}', [EmailTemplateController::class, 'update']);
    Route::delete('/{template}', [EmailTemplateController::class, 'destroy']);
});

Route::middleware(['bearer', 'role:admin', 'throttle:email-templates-send-test'])->prefix('admin/email-templates')->group(function () {
    Route::post('/send-test', [EmailTemplateController::class, 'sendTest']);
});

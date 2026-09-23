<?php

use App\Features\EmailNotifications\Controllers\EmailTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['bearer', 'role:admin'])->prefix('admin/email-templates')->group(function () {
    Route::get('/', [EmailTemplateController::class, 'index']);
    Route::post('/', [EmailTemplateController::class, 'store']);
    Route::get('/{emailTemplate}', [EmailTemplateController::class, 'show']);
    Route::patch('/{emailTemplate}', [EmailTemplateController::class, 'update']);
    Route::delete('/{emailTemplate}', [EmailTemplateController::class, 'destroy']);
    Route::post('/send-test', [EmailTemplateController::class, 'sendTest']);
    Route::post('/seed', [EmailTemplateController::class, 'seed']);
});

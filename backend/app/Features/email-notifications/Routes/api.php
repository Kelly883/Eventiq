<?php

use App\Features\EmailNotifications\Controllers\EmailTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('email-templates')->group(function () {
    Route::get('/', [EmailTemplateController::class, 'index']);
    Route::post('/', [EmailTemplateController::class, 'store']);
    Route::get('/{emailTemplate}', [EmailTemplateController::class, 'show']);
    Route::put('/{emailTemplate}', [EmailTemplateController::class, 'update']);
    Route::delete('/{emailTemplate}', [EmailTemplateController::class, 'destroy']);
    Route::post('/{emailTemplate}/send-test', [EmailTemplateController::class, 'sendTest']);
});

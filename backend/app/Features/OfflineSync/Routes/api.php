<?php

use Illuminate\Support\Facades\Route;
use App\Features\OfflineSync\Controllers\OfflineSyncController;

Route::prefix('offline-sync')->middleware('auth:sanctum')->group(function () {
    Route::post('/enqueue', [OfflineSyncController::class, 'enqueue']);
    Route::post('/apply-due', [OfflineSyncController::class, 'applyDue']);
});


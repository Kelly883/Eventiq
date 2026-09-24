<?php

use Illuminate\Support\Facades\Route;
use App\Features\Refunds\Controllers\RefundController;
use App\Features\Refunds\Controllers\AdminRefundController;

// User refund routes
Route::middleware(['bearer', 'throttle:refund-request'])->prefix('refunds')->group(function () {
    Route::post('/request', [RefundController::class, 'requestRefund']);
});

Route::middleware(['bearer', 'throttle:refund-status'])->prefix('refunds')->group(function () {
    Route::get('/status/{id}', [RefundController::class, 'getStatus']);
});

// Admin refund routes
Route::middleware(['bearer', 'role:admin'])->prefix('admin/refunds')->group(function () {
    Route::get('/', [AdminRefundController::class, 'index']);
    Route::put('/{id}/approve', [AdminRefundController::class, 'approve']);
    Route::put('/{id}/reject', [AdminRefundController::class, 'reject']);
});

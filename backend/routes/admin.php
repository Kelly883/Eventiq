<?php

use Illuminate\Support\Facades\Route;
use App\Features\admin\Controllers\AdminDashboardController;
use App\Features\admin\Controllers\AdminUserController;
use App\Features\admin\Controllers\AdminEventController;
use App\Features\admin\Controllers\AdminPaymentController;
use App\Features\admin\Controllers\AdminTicketController;

Route::middleware(['auth:sanctum', 'role:admin', 'throttle:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/events', [AdminEventController::class, 'index']);
    Route::get('/payments/reconciliation', [AdminPaymentController::class, 'index']);
    Route::get('/tickets', [AdminTicketController::class, 'index']);
    Route::get('/tickets/{id}', [AdminTicketController::class, 'show']);
    Route::post('/tickets/{id}/purge', [AdminTicketController::class, 'purge']);
});


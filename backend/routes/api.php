<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // User-facing permission routes
    Route::post('/permissions/request', [PermissionController::class, 'submitPermissionRequest']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{role}/assign', [RoleController::class, 'assignRole']);
    Route::post('roles/{role}/remove', [RoleController::class, 'removeRole']);
    
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::put('roles/{role}/permissions', [PermissionController::class, 'updateRolePermissions']);
    Route::get('audit-log', [PermissionController::class, 'auditLog']);
    Route::get('permission-requests', [PermissionController::class, 'getPermissionRequests']);
    Route::post('permission-requests/{request}/approve', [PermissionController::class, 'approvePermissionRequest']);
    Route::post('permission-requests/{request}/reject', [PermissionController::class, 'rejectPermissionRequest']);
});

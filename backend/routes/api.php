<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Organizer\EventController;
use App\Features\Ticketing\Controllers\EventTicketingController;
use App\Features\Pricing\Controllers\PricingWindowController;
use App\Features\Pricing\Controllers\PricingController;
use App\Features\Delivery\Controllers\DeliveryController;

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

    // Organizer routes
    Route::prefix('organizer')->group(function () {
        // Organizer profile
        Route::get('/profile', [OrganizerController::class, 'edit']);
        Route::put('/profile', [OrganizerController::class, 'update']);

        // Organizer events
        Route::apiResource('events', EventController::class);

        // Event ticketing
        Route::prefix('events/{event}')->group(function () {
            Route::put('/ticketing', [EventTicketingController::class, 'update']);

            // Event pricing (organizer)
            Route::apiResource('pricing-windows', PricingWindowController::class);
        });
    });
});

// Public event pricing (attendee)
Route::get('/events/{event}/pricing', [PricingController::class, 'show']);

// Public organizer profile
Route::get('/organizers/{organizer}', [OrganizerController::class, 'show']);

// Ticket Delivery Endpoints
Route::middleware('auth:sanctum')->group(function () {
    // User delivery routes
    Route::prefix('delivery')->group(function () {
        //
    });
    
    // Admin delivery routes
    Route::middleware('role:admin')->prefix('admin/delivery')->group(function () {
        //
    });
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

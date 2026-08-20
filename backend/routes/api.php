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

// Include Push Notifications routes
require base_path('app/Features/push-notifications/Routes/api.php');

// Include QR Code Ticketing routes
require base_path('app/Features/qr-code-ticketing/Routes/api.php');

// Include Check-In routes
require base_path('app/Features/check-in/Routes/api.php');

// Include Email Notifications routes
require base_path('app/Features/email-notifications/Routes/api.php');

// Include Inventory routes
require base_path('app/Features/inventory/Routes/api.php');

// Include Checkout routes
require base_path('app/Features/checkout/Routes/api.php');

// Include Organizer Profile routes
require base_path('app/Features/organizer-profile/Routes/api.php');

// Include Refunds routes
require base_path('app/Features/refunds/Routes/api.php');

// Include Payouts routes
require base_path('app/Features/payouts/Routes/api.php');

// Include Analytics routes
require base_path('app/Features/analytics/Routes/api.php');

// Include Events Calendar routes
require base_path('app/Features/events-calendar/Routes/api.php');

// Include Compliance routes
require base_path('app/Features/compliance/Routes/api.php');

// Include Fraud detection routes
require base_path('app/Features/fraud/Routes/api.php');


// Include Admin routes (platform management)
require base_path('routes/admin.php');

// Include Payment routes
require base_path('app/Features/payment/Routes/api.php');

// Include OfflineSync routes
require base_path('app/Features/offline-sync/Routes/api.php');

// Include API Keys routes
require base_path('app/Features/api-keys/Routes/api.php');




// Public routes
Route::middleware('throttle:auth')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
});
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

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
Route::middleware(['auth:sanctum', 'role:admin', 'throttle:admin'])->prefix('admin')->group(function () {
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

// Public API integration routes are protected by API keys.
Route::middleware('api.key')->prefix('v1')->group(function () {
    Route::get('/events', function (\Illuminate\Http\Request $request) {
        abort_unless(in_array('events:read', $request->attributes->get('api_key_scopes', []), true), 403);

        return \App\Models\Event::query()
            ->where('organizer_id', $request->attributes->get('organizer')->id)
            ->latest()
            ->get();
    });
});

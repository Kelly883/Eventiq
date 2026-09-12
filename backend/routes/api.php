<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Organizer\EventController;
use App\Http\Controllers\Public\EventController as PublicEventController;
use App\Http\Controllers\Public\NewsletterController;
use App\Features\Ticketing\Controllers\EventTicketingController;
use App\Features\Pricing\Controllers\PricingWindowController;
use App\Features\Pricing\Controllers\PricingController;
use App\Features\Delivery\Controllers\DeliveryController;

// Include Push Notifications routes
require base_path('app/Features/PushNotifications/Routes/api.php');

// Include QR Code Ticketing routes
require base_path('app/Features/QRCodeTicketing/Routes/api.php');

// Include Check-In routes
require base_path('app/Features/CheckIn/Routes/api.php');

// Include Email Notifications routes
require base_path('app/Features/EmailNotifications/Routes/api.php');

// Include Inventory routes
require base_path('app/Features/Inventory/Routes/api.php');

// Include Checkout routes
require base_path('app/Features/Checkout/Routes/api.php');

// Include Organizer Profile routes
require base_path('app/Features/OrganizerProfile/Routes/api.php');

// Include Refunds routes
require base_path('app/Features/Refunds/Routes/api.php');

// Include Payouts routes
require base_path('app/Features/Payouts/Routes/api.php');

// Include Analytics routes
require base_path('app/Features/Analytics/Routes/api.php');

// Include Events Calendar routes
require base_path('app/Features/EventsCalendar/Routes/api.php');

// Include Compliance routes
require base_path('app/Features/Compliance/Routes/api.php');

// Include Fraud detection routes
require base_path('app/Features/Fraud/Routes/api.php');


// Include Admin routes (platform management)
require base_path('routes/admin.php');

// Include Payment routes
require base_path('app/Features/Payment/Routes/api.php');

// Include OfflineSync routes
require base_path('app/Features/OfflineSync/Routes/api.php');

// Include API Keys routes

// Include Accessibility routes
require base_path('app/Features/Accessibility/Routes/api.php');

// Include Localization routes

// Include Developer portal routes
require base_path('app/Features/ApiKeys/Routes/developer-api.php');
require base_path('app/Features/Localization/Routes/api.php');
require base_path('app/Features/ApiKeys/Routes/api.php');




// Public routes
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:forgot-password');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth');

// Protected routes — support both Sanctum sessions and Bearer tokens.
Route::middleware('bearer')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // User-facing permission routes
    Route::post('/permissions/request', [PermissionController::class, 'submitPermissionRequest']);

    // Organizer routes
    Route::prefix('organizer')->group(function () {
        // Organizer profile
        Route::get('/profile', [OrganizerController::class, 'edit']);
        Route::put('/profile', [OrganizerController::class, 'update']);

        // Organizer events — throttled per organizer (60/min) with stricter create (30/min) and banner (10/min)
        Route::middleware('throttle:organizer-events')->group(function () {
            Route::apiResource('events', EventController::class)->except(['store']);
        });
        Route::post('events', [EventController::class, 'store'])->middleware('throttle:organizer-events-create');
        Route::post('events/{event}/upload-banner', [EventController::class, 'uploadBanner'])->middleware('throttle:organizer-banner');

        // Event ticketing — supports both PUT and PATCH per spec (ticketTiers sync)
        Route::prefix('events/{event}')->group(function () {
            Route::match(['put', 'patch'], '/ticketing', [EventTicketingController::class, 'update'])
                ->middleware('throttle:30,1'); // Fix #2: Rate limiting - 30 requests/minute

            // Event pricing (organizer)
            Route::apiResource('pricing-windows', PricingWindowController::class);
            Route::get('pricing/preview', [PricingWindowController::class, 'preview']);
        });
    });
});

// Public event pricing (attendee)
Route::get('/events/{event}/pricing', [PricingController::class, 'show']);

// Public event discovery (homepage, anonymous browsing) -- was entirely
// missing; frontend/src/features/homepage/hooks/useHomepageData.js has
// been calling these paths already, every request 404ing invisibly.
// event-ticketing-prd-export's EventBrowsePage/CategoryBrowsePage specs
// both call for "Rate limit 30/min per IP to prevent scraping" -- applied
// here via the 'discovery' limiter registered in AppServiceProvider.
Route::middleware('throttle:discovery')->group(function () {
    Route::get('/events', [PublicEventController::class, 'index']);
    Route::get('/categories', [PublicEventController::class, 'categories']);
    Route::get('/events/{event}', [PublicEventController::class, 'show']);
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
});

// Public organizer profile is handled in OrganizerProfile Routes (with isPublic + rate limit)
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

// Offline sync routes
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('throttle:30,1')->patch('/notifications/device-tokens/{token}/offline-status', [App\Features\PushNotifications\Controllers\DeviceTokenController::class, 'updateOfflineStatus']);
    Route::middleware('throttle:60,1')->get('/me/tickets/for-offline-sync', [App\Features\OfflineSync\Controllers\OfflineSyncController::class, 'getTicketsForOfflineSync']);
    Route::post('/me/device-token/rotate', [App\Features\PushNotifications\Controllers\DeviceTokenController::class, 'rotate'])->middleware('throttle:5,1');
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

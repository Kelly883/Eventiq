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

// Step 137: User Role & Permission Management — 4 admin endpoints (spec)
// All admin-only, IsAdmin middleware + Policy checks inside controller, 10/min per admin where noted
// Uses `bearer` (handles both Sanctum cookie and Bearer token from POST /auth/login)
// Throttle runs AFTER bearer (so it can key by user id) but BEFORE isAdmin
// so even non-admin requests are rate-limited. An outer api throttle (60/min by IP)
// also applies before bearer to stop unauthenticated floods from bypassing.
Route::middleware(['throttle:api', 'bearer'])->prefix('admin')->group(function () {
    // GET /api/admin/users/list — paginated users with roles/permissions (10/min)
    Route::middleware(['throttle:admin-users', 'isAdmin'])->group(function () {
        Route::get('/users/list', [\App\Http\Controllers\Admin\UserManagementController::class, 'listUsers']);
    });

    // POST /api/admin/roles/assign — assign role to users (10/min)
    Route::middleware(['throttle:admin-roles-assign', 'isAdmin'])->group(function () {
        Route::post('/roles/assign', [\App\Http\Controllers\Admin\UserManagementController::class, 'assignRole']);
    });

    // POST /api/admin/permissions/update — grant/revoke permissions (10/min)
    Route::middleware(['throttle:admin-permissions', 'isAdmin'])->group(function () {
        Route::post('/permissions/update', [\App\Http\Controllers\Admin\UserManagementController::class, 'updatePermissions']);
    });

    // GET /api/admin/audit-log/list — audit logs sorted desc, filtered (general api limit: 60/min)
    Route::middleware(['throttle:api', 'isAdmin'])->group(function () {
        Route::get('/audit-log/list', [\App\Http\Controllers\Admin\UserManagementController::class, 'auditLogList']);
    });
});

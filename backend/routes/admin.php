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
Route::middleware(['bearer'])->prefix('admin')->group(function () {
    Route::middleware(['isAdmin'])->group(function () {
        // GET /api/admin/users/list — paginated users with roles/permissions
        Route::get('/users/list', [\App\Http\Controllers\Admin\UserManagementController::class, 'listUsers'])
            ->middleware('throttle:admin-users');

        // POST /api/admin/roles/assign — assign role to users (transactional, audit, session invalidation)
        Route::post('/roles/assign', [\App\Http\Controllers\Admin\UserManagementController::class, 'assignRole'])
            ->middleware('throttle:admin-roles-assign');

        // POST /api/admin/permissions/update — grant/revoke permissions (high-risk check)
        Route::post('/permissions/update', [\App\Http\Controllers\Admin\UserManagementController::class, 'updatePermissions'])
            ->middleware('throttle:admin-permissions');

        // GET /api/admin/audit-log/list — audit logs sorted desc, filtered
        Route::get('/audit-log/list', [\App\Http\Controllers\Admin\UserManagementController::class, 'auditLogList'])
            ->middleware('throttle:admin-audit-log');
    });
});

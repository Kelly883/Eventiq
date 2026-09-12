<?php

use Illuminate\Support\Facades\Route;
use App\Features\Inventory\Controllers\InventoryController;

Route::middleware(['bearer'])->group(function () {
    // Summary - 20/min per user
    Route::get('/organizer/events/{eventId}/inventory/summary', [InventoryController::class, 'summary'])
        ->middleware('throttle:inventory-summary');

    // Detailed inventory with pricing windows - 20/min per user
    Route::get('/organizer/events/{eventId}/inventory', [InventoryController::class, 'inventory'])
        ->middleware('throttle:inventory-detail');

    // Adjust inventory - 5/min per user (prevent abuse)
    Route::patch('/organizer/events/{eventId}/inventory/adjust', [InventoryController::class, 'adjust'])
        ->middleware('throttle:inventory-adjust');

    // Export - 10/min per user
    Route::get('/organizer/events/{eventId}/inventory/export', [InventoryController::class, 'export'])
        ->middleware('throttle:inventory-export');

    // Audit log - 20/min per user, paginated, filtered, sorted by most recent
    Route::get('/organizer/events/{eventId}/inventory/audit-log', [InventoryController::class, 'auditLog'])
        ->middleware('throttle:inventory-audit');
});

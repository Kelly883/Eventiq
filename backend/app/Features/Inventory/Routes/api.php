<?php

use Illuminate\Support\Facades\Route;
use App\Features\Inventory\Controllers\InventoryController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/organizer/events/{eventId}/inventory/summary', [InventoryController::class, 'summary']);
});

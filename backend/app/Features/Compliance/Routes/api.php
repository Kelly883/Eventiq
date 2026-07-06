<?php

use Illuminate\Support\Facades\Route;
use App\Features\Compliance\Controllers\AuditLogController;
use App\Features\Compliance\Controllers\ComplianceReportController;

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/compliance')->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/reports', [ComplianceReportController::class, 'index']);
    Route::post('/reports/generate', [ComplianceReportController::class, 'generate']);
});


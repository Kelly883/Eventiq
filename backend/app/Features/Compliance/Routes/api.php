<?php

use Illuminate\Support\Facades\Route;
use App\Features\Compliance\Controllers\AuditLogController;
use App\Features\Compliance\Controllers\ComplianceReportController;

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/compliance')->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{logId}', [AuditLogController::class, 'show']);
    Route::get('/audit-logs/export', [AuditLogController::class, 'export']);
    Route::post('/audit-logs/bulk-tag', [AuditLogController::class, 'bulkTag']);
    Route::get('/reports', [ComplianceReportController::class, 'index']);
    Route::post('/reports/generate', [ComplianceReportController::class, 'generate']);
    Route::get('/reports/{reportId}/download', [ComplianceReportController::class, 'download']);
    Route::get('/checklist', [ComplianceReportController::class, 'checklist']);
});


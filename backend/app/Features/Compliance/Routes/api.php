<?php

use Illuminate\Support\Facades\Route;
use App\Features\Compliance\Controllers\AuditLogController;
use App\Features\Compliance\Controllers\ComplianceReportController;

Route::middleware(['bearer', 'role:admin', 'throttle:compliance-list'])->prefix('admin/compliance')->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/summary', [AuditLogController::class, 'summary']);
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->middleware('throttle:compliance-export');
    Route::get('/audit-logs/{logId}', [AuditLogController::class, 'show']);
    Route::post('/audit-logs/bulk-tag', [AuditLogController::class, 'bulkTag'])->middleware('throttle:compliance-bulk-tag');
    Route::get('/reports', [ComplianceReportController::class, 'index']);
    Route::post('/reports/generate', [ComplianceReportController::class, 'generate'])->middleware('throttle:compliance-report-generate');
    Route::get('/reports/{reportId}/download', [ComplianceReportController::class, 'download']);
    Route::get('/checklist', [ComplianceReportController::class, 'checklist']);
});

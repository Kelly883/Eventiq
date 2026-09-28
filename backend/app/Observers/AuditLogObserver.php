<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Services\AdminDashboardCacheService;

class AuditLogObserver
{
    public function created(AuditLog $auditLog): void
    {
        if (in_array($auditLog->status, ['failure', 'warning'])) {
            AdminDashboardCacheService::invalidateAlerts();
        }
    }
}

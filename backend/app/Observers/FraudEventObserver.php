<?php

namespace App\Observers;

use App\Features\Fraud\Models\FraudEvent;
use App\Services\AdminDashboardCacheService;

class FraudEventObserver
{
    public function created(FraudEvent $fraudEvent): void
    {
        if (in_array($fraudEvent->status, ['flagged', 'auto_blocked'])) {
            AdminDashboardCacheService::invalidateAlerts();
        }
    }

    public function updated(FraudEvent $fraudEvent): void
    {
        if (in_array($fraudEvent->status, ['flagged', 'auto_blocked'])) {
            AdminDashboardCacheService::invalidateAlerts();
        }
    }
}

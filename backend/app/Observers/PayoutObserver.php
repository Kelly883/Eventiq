<?php

namespace App\Observers;

use App\Features\Payouts\Models\Payout;
use App\Services\AdminDashboardCacheService;

class PayoutObserver
{
    public function created(Payout $payout): void
    {
        if ($payout->status === Payout::STATUS_FAILED) {
            AdminDashboardCacheService::invalidateAlerts();
        }
        AdminDashboardCacheService::invalidateOverview();
    }

    public function updated(Payout $payout): void
    {
        if ($payout->status === Payout::STATUS_FAILED) {
            AdminDashboardCacheService::invalidateAlerts();
        }
        AdminDashboardCacheService::invalidateOverview();
    }
}

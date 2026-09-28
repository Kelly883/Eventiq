<?php

namespace App\Observers;

use App\Features\Checkout\Models\Payment;
use App\Services\AdminDashboardCacheService;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        AdminDashboardCacheService::invalidateOverview();
    }

    public function updated(Payment $payment): void
    {
        AdminDashboardCacheService::invalidateOverview();
    }
}

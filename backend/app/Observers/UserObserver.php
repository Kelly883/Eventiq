<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AdminDashboardCacheService;

class UserObserver
{
    public function created(User $user): void
    {
        AdminDashboardCacheService::invalidateOverview();
    }
}

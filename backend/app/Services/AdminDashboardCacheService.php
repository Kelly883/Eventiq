<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AdminDashboardCacheService
{
    public static function invalidateOverview(): void
    {
        $periods = ['24h', '7d', '30d', 'all_time'];
        foreach ($periods as $period) {
            Cache::forget('admin.dashboard.overview.' . $period);
            Cache::forget('admin.dashboard.overview.previous.' . $period);
        }
    }

    public static function invalidateAlerts(): void
    {
        Cache::forget('admin.dashboard.alerts');
    }
}

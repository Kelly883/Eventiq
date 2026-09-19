<?php

namespace App\Observers;

use App\Models\AnalyticsSalesTimeline;
use App\Models\AnalyticsEventsMetric;
use App\Models\Event;
use Illuminate\Support\Facades\Cache;

class AnalyticsSalesTimelineObserver
{
    /**
     * When a new sale is recorded, update the pre-aggregated metrics
     * so the summary endpoint doesn't need to SUM() millions of rows.
     */
    public function created(AnalyticsSalesTimeline $sale): void
    {
        $metric = AnalyticsEventsMetric::where('event_id', $sale->event_id)->first();

        if ($metric) {
            $metric->increment('total_revenue', $sale->total_amount);
            $metric->increment('total_tickets_sold', $sale->quantity);

            // Recalculate average ticket price
            $totalSold = $metric->total_tickets_sold;
            $avgPrice = $totalSold > 0 ? round($metric->total_revenue / $totalSold, 2) : 0;
            $metric->update([
                'average_ticket_price' => $avgPrice,
                'last_updated_at' => now(),
            ]);
        }

        // Invalidate analytics caches for this event so fresh data appears immediately
        $this->invalidateEventCaches($sale->event_id);
    }

    /**
     * Invalidate all analytics caches for an event.
     * Called on new sale so 30s cache doesn't hide real-time data.
     */
    private function invalidateEventCaches(string $eventId): void
    {
        // Summary caches are keyed with eventId + user + date range.
        // Since we can't enumerate user IDs, we use a tag-based invalidation pattern.
        // Simple approach: increment a version counter that's part of the cache key.
        $cacheKey = "analytics:version:{$eventId}";
        $current = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $current + 1, 3600); // 1h TTL on version counter
    }

    /**
     * Get the current cache version for an event.
     */
    public static function getCacheVersion(string $eventId): int
    {
        return Cache::get("analytics:version:{$eventId}", 0);
    }
}

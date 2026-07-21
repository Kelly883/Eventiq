<?php

namespace App\Observers;

use App\Models\Event;
use App\Models\AnalyticsEventsMetric;
use Illuminate\Support\Str;

class EventObserver
{
    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        // Automatically create analytics_events_metrics record when event is created
        AnalyticsEventsMetric::create([
            'id' => Str::uuid(),
            'event_id' => $event->id,
            'organizer_id' => $event->organizer_id,
            'total_revenue' => 0,
            'total_tickets_sold' => 0,
            'total_page_views' => 0,
            'total_ticket_page_views' => 0,
            'conversion_rate' => 0,
            'average_ticket_price' => 0,
            'peak_sales_hour' => null,
            'top_ticket_tier_id' => null,
            'last_updated_at' => now(),
        ]);
    }

    /**
     * Handle the Event "updated" event.
     */
    public function updated(Event $event): void
    {
        // Update last_updated_at in analytics when event changes
        $analytics = AnalyticsEventsMetric::where('event_id', $event->id)->first();
        if ($analytics) {
            $analytics->update(['last_updated_at' => now()]);
        }
    }
}
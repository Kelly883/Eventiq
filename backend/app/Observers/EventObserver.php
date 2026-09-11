<?php

namespace App\Observers;

use App\Models\Event;
use App\Models\AnalyticsEventsMetric;
use App\Models\EventsCalendarSummary;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EventObserver
{
    public function created(Event $event): void
    {
        AnalyticsEventsMetric::firstOrCreate(
            ['event_id' => $event->id],
            [
                'id' => (string) Str::uuid(),
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
            ]
        );

        if ($event->start_datetime) {
            EventsCalendarSummary::refreshForDate($event->start_datetime->format('Y-m-d'));
        }

        // totalEventsCreated is now handled by IncrementTotalEventsCreated job (dispatched from EventController@store)
        // to avoid double-count and allow ShouldBeUnique deduplication. Observer no longer increments directly.
    }

    public function updated(Event $event): void
    {
        $analytics = AnalyticsEventsMetric::where('event_id', $event->id)->first();
        if ($analytics) {
            $analytics->update(['last_updated_at' => now()]);
        }

        if ($event->start_datetime) {
            EventsCalendarSummary::refreshForDate($event->start_datetime->format('Y-m-d'));
        }
        if ($event->getOriginal('start_datetime') && $event->getOriginal('start_datetime') != $event->start_datetime) {
            EventsCalendarSummary::refreshForDate(
                Carbon::parse($event->getOriginal('start_datetime'))->format('Y-m-d')
            );
        }
    }

    public function deleted(Event $event): void
    {
        if ($event->start_datetime) {
            EventsCalendarSummary::refreshForDate($event->start_datetime->format('Y-m-d'));
        }

        // totalEventsCreated handled by DecrementTotalEventsCreated job
    }

    public function restored(Event $event): void
    {
        if ($event->start_datetime) {
            EventsCalendarSummary::refreshForDate($event->start_datetime->format('Y-m-d'));
        }

        // totalEventsCreated handled by IncrementTotalEventsCreated job on restore if needed
    }
}

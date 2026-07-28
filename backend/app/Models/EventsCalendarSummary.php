<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EventsCalendarSummary extends Model
{
    protected $table = 'events_calendar_summary';

    protected $fillable = [
        'event_date',
        'total_events',
        'total_capacity',
        'published_events',
        'published_capacity',
        'draft_events',
        'cancelled_events',
        'last_refreshed_at',
    ];

    protected $casts = [
        'total_events' => 'integer',
        'total_capacity' => 'integer',
        'published_events' => 'integer',
        'published_capacity' => 'integer',
        'draft_events' => 'integer',
        'cancelled_events' => 'integer',
        'last_refreshed_at' => 'datetime',
    ];

    /**
     * Get the event date as a Carbon instance.
     */
    public function getEventDateAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value) : null;
    }

    /**
     * Set the event date as a Y-m-d string.
     */
    public function setEventDateAttribute($value)
    {
        $this->attributes['event_date'] = $value instanceof \Carbon\Carbon ? $value->format('Y-m-d') : $value;
    }

    /**
     * Scope a query to only include dates within a range.
     */
    public function scopeInDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('event_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include dates with published events.
     */
    public function scopeHasPublishedEvents($query)
    {
        return $query->where('published_events', '>', 0);
    }

    /**
     * Refresh the summary data from the events table for a specific date.
     */
    public static function refreshForDate(string $date): void
    {
        $stats = DB::table('events')
            ->select(
                DB::raw("DATE(start_datetime) as event_date"),
                DB::raw("COUNT(*) as total_events"),
                DB::raw("COALESCE(SUM(capacity), 0) as total_capacity"),
                DB::raw("SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_events"),
                DB::raw("SUM(CASE WHEN status = 'published' THEN COALESCE(capacity, 0) ELSE 0 END) as published_capacity"),
                DB::raw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_events"),
                DB::raw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_events")
            )
            ->whereNotNull('start_datetime')
            ->where(DB::raw("DATE(start_datetime)"), '=', $date)
            ->first();

        if ($stats && $stats->total_events > 0) {
            self::updateOrCreate(
                ['event_date' => $stats->event_date],
                [
                    'total_events' => $stats->total_events,
                    'total_capacity' => $stats->total_capacity,
                    'published_events' => $stats->published_events,
                    'published_capacity' => $stats->published_capacity,
                    'draft_events' => $stats->draft_events,
                    'cancelled_events' => $stats->cancelled_events,
                    'last_refreshed_at' => now(),
                ]
            );
        } else {
            self::where('event_date', $date)->delete();
        }
    }

    /**
     * Full refresh of the entire calendar summary table.
     */
    public static function fullRefresh(): void
    {
        self::truncate();

        DB::statement("
            INSERT INTO events_calendar_summary 
                (event_date, total_events, total_capacity, published_events, published_capacity, draft_events, cancelled_events, last_refreshed_at, created_at, updated_at)
            SELECT 
                DATE(start_datetime) as event_date,
                COUNT(*) as total_events,
                COALESCE(SUM(capacity), 0) as total_capacity,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_events,
                SUM(CASE WHEN status = 'published' THEN COALESCE(capacity, 0) ELSE 0 END) as published_capacity,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_events,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_events,
                NOW() as last_refreshed_at,
                NOW() as created_at,
                NOW() as updated_at
            FROM events
            WHERE start_datetime IS NOT NULL
            GROUP BY DATE(start_datetime)
        ");
    }
}

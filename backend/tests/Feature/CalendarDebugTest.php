<?php

namespace Tests\Feature;

use App\Models\EventsCalendarSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalendarDebugTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debug_calendar_column_type(): void
    {
        // Create a summary record
        EventsCalendarSummary::create([
            'event_date' => '2026-09-30',
            'total_events' => 1,
        ]);
        
        // Check the raw database value
        $raw = DB::table('events_calendar_summary')->first();
        echo "\nRaw DB value for event_date: " . $raw->event_date . "\n";
        
        // Check what the model returns
        $model = EventsCalendarSummary::first();
        echo "Model event_date: " . $model->event_date . " (type: " . gettype($model->event_date) . ")\n";
        echo "Model event_date format: " . $model->event_date->format('Y-m-d H:i:s') . "\n";
        
        // Test whereBetween with different upper bounds
        $result1 = EventsCalendarSummary::whereBetween('event_date', ['2026-09-01', '2026-09-30'])->count();
        $result2 = EventsCalendarSummary::whereBetween('event_date', ['2026-09-01', '2026-09-30 23:59:59'])->count();
        $result3 = EventsCalendarSummary::whereDate('event_date', '>=', '2026-09-01')
            ->whereDate('event_date', '<=', '2026-09-30')
            ->count();
        
        echo "\nwhereBetween ['2026-09-01', '2026-09-30']: $result1\n";
        echo "whereBetween ['2026-09-01', '2026-09-30 23:59:59']: $result2\n";
        echo "whereDate >= '2026-09-01' AND <= '2026-09-30': $result3\n";
        
        $this->assertTrue(true);
    }
}

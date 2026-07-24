<?php

namespace App\Console\Commands;

use App\Models\EventsCalendarSummary;
use Illuminate\Console\Command;

class RefreshCalendarSummary extends Command
{
    protected $signature = 'calendar:refresh-summary {--full : Perform a full refresh (truncate and rebuild)}';
    protected $description = 'Refresh the events_calendar_summary materialized table for fast calendar queries';

    public function handle(): int
    {
        $this->info('Refreshing calendar summary...');

        $start = microtime(true);

        if ($this->option('full')) {
            EventsCalendarSummary::fullRefresh();
            $this->info('Full refresh completed.');
        } else {
            // Incremental refresh: recalculate only dates that have events or were recently modified
            $dates = \Illuminate\Support\Facades\DB::table('events')
                ->selectRaw('DISTINCT DATE(start_datetime) as event_date')
                ->whereNotNull('start_datetime')
                ->where('updated_at', '>=', now()->subHours(24))
                ->pluck('event_date');

            $count = 0;
            foreach ($dates as $date) {
                EventsCalendarSummary::refreshForDate($date);
                $count++;
            }

            $this->info("Incremental refresh completed for {$count} date(s).");
        }

        $duration = (microtime(true) - $start) * 1000;
        $this->info("Completed in " . round($duration, 2) . "ms");

        return Command::SUCCESS;
    }
}

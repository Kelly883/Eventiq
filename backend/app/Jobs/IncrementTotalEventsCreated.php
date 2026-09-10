<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IncrementTotalEventsCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int|string $organizerId) {}

    public function handle(): void
    {
        try {
            // Recalculate to avoid double-count if EventObserver already incremented
            $count = \App\Models\Event::where('organizer_id', $this->organizerId)->count();
            \App\Models\Organizer::where('id', $this->organizerId)->update(['totalEventsCreated' => $count]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('IncrementTotalEventsCreated failed', [
                'organizer_id' => $this->organizerId,
                'error' => $e->getMessage(),
            ]);
            // Fallback increment
            try {
                \App\Models\Organizer::where('id', $this->organizerId)->increment('totalEventsCreated');
            } catch (\Throwable $inner) {}
        }
    }
}

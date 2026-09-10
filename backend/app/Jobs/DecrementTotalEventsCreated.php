<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DecrementTotalEventsCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int|string $organizerId) {}

    public function handle(): void
    {
        try {
            // Recalculate to ensure consistency after soft delete
            $count = \App\Models\Event::where('organizer_id', $this->organizerId)->count();
            \App\Models\Organizer::where('id', $this->organizerId)->update(['totalEventsCreated' => $count]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DecrementTotalEventsCreated failed', [
                'organizer_id' => $this->organizerId,
                'error' => $e->getMessage(),
            ]);
            try {
                \App\Models\Organizer::where('id', $this->organizerId)->where('totalEventsCreated', '>', 0)->decrement('totalEventsCreated');
            } catch (\Throwable $inner) {}
        }
    }
}

<?php

namespace App\Features\Delivery\Jobs;

use App\Features\Delivery\Models\DeliveryEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ArchiveOldDeliveryEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds before the job should timeout.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param int $retentionDays Number of days to retain delivered events before archiving
     */
    public function __construct(
        public int $retentionDays = 90,
    ) {
    }

    /**
     * Execute the job.
     *
     * Archives delivered/failed delivery events older than the retention period
     * by setting their archived_at timestamp. This keeps the primary table lean
     * while preserving audit trail data.
     */
    public function handle(): void
    {
        $cutoffDate = now()->subDays($this->retentionDays);

        Log::info("ArchiveOldDeliveryEventsJob: Archiving events older than {$cutoffDate->toDateTimeString()}");

        // Archive delivered events older than retention period
        $deliveredCount = DeliveryEvent::whereNull('archived_at')
            ->whereIn('status', ['delivered', 'sent', 'failed', 'bounced', 'cancelled'])
            ->where('created_at', '<', $cutoffDate)
            ->update(['archived_at' => now()]);

        Log::info("ArchiveOldDeliveryEventsJob: Archived {$deliveredCount} old delivery events");

        // Optionally move large payload data to separate table before archiving
        $this->migrateLargePayloads($cutoffDate);
    }

    /**
     * Migrate large JSON payloads from delivery_events to delivery_event_data
     * table for events being archived. This keeps the row size small.
     */
    protected function migrateLargePayloads($cutoffDate): void
    {
        $events = DeliveryEvent::whereNull('archived_at')
            ->where('created_at', '<', $cutoffDate)
            ->whereNotNull('payload')
            ->where(function ($q) {
                $q->whereRaw('LENGTH(JSON_EXTRACT(payload, "$")) > 500');
            })
            ->get();

        $migrated = 0;
        foreach ($events as $event) {
            try {
                $event->eventData()->create([
                    'payload' => $event->payload,
                    'provider_response' => $event->provider_response,
                    'error_message' => $event->error_message,
                ]);

                // Clear the in-table payload to free space
                $event->updateQuietly([
                    'payload' => null,
                    'provider_response' => null,
                    'error_message' => null,
                ]);

                $migrated++;
            } catch (\Throwable $e) {
                Log::warning("ArchiveOldDeliveryEventsJob: Failed to migrate payload for event {$event->id}: {$e->getMessage()}");
            }
        }

        if ($migrated > 0) {
            Log::info("ArchiveOldDeliveryEventsJob: Migrated {$migrated} large payloads to delivery_event_data table");
        }
    }
}

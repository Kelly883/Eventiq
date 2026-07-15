<?php

namespace App\Features\Delivery\Jobs;

use App\Services\TicketDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param string $channel 'email' | 'sms' | 'dashboard'
     * @param array $data Channel-specific payload, matching
     *   TicketDeliveryService::send()'s expected shape
     */
    public function __construct(
        public string $channel,
        public array $data,
        public int $attempts = 1,
    ) {
    }

    public function handle(TicketDeliveryService $deliveryService): void
    {
        Log::info("Retrying ticket delivery (attempt #{$this->attempts}) via {$this->channel}");

        try {
            $result = $deliveryService->send($this->channel, $this->data);

            if (($result['sent'] ?? $result['recorded'] ?? false) !== true) {
                throw new \RuntimeException("Delivery via {$this->channel} did not succeed: " . json_encode($result));
            }
        } catch (\Throwable $e) {
            if ($this->attempts < 3) {
                Log::info('Scheduling retry #' . ($this->attempts + 1) . ' for delivery failure: ' . $e->getMessage());
                dispatch(new self($this->channel, $this->data, $this->attempts + 1))->delay(now()->addMinutes(5));
            } else {
                Log::error("Exceeded maximum delivery retries for channel {$this->channel}. Error: " . $e->getMessage());
            }
        }
    }
}

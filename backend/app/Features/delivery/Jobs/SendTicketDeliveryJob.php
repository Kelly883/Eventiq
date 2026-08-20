<?php

namespace App\Features\Delivery\Jobs;

use App\Services\TicketDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTicketDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // seconds between retries

    /**
     * @param string $channel 'email' | 'sms' | 'dashboard'
     * @param array $data Channel-specific payload, see TicketDeliveryService::send()
     */
    public function __construct(
        public string $channel,
        public array $data,
    ) {
    }

    public function handle(TicketDeliveryService $deliveryService): void
    {
        $result = $deliveryService->send($this->channel, $this->data);

        if (($result['sent'] ?? $result['recorded'] ?? false) !== true) {
            Log::warning('SendTicketDeliveryJob: delivery did not succeed', [
                'channel' => $this->channel,
                'result' => $result,
            ]);

            // Let Laravel's retry/backoff handle transient failures rather
            // than throwing unconditionally - RetryFailedDeliveryJob picks
            // up anything that exhausts $tries here.
            $this->fail(new \RuntimeException("Ticket delivery via {$this->channel} failed"));
        }
    }
}

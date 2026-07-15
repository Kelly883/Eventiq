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

    protected array $ticketData;
    protected string $method;
    protected string $recipient;
    protected int $attempts;

    /**
     * Create a new job instance.
     */
    public function __construct(array $ticketData, string $method, string $recipient, int $attempts = 1)
    {
        $this->ticketData = $ticketData;
        $this->method = $method;
        $this->recipient = $recipient;
        $this->attempts = $attempts;
    }

    /**
     * Execute the job.
     */
    public function handle(TicketDeliveryService $deliveryService): void
    {
        Log::info("Retrying SendTicketDeliveryJob (Attempt #{$this->attempts}) for method: {$this->method}");

        try {
            switch ($this->method) {
                case 'email':
                    $deliveryService->sendViaEmail($this->recipient, $this->ticketData);
                    break;
                case 'sms':
                    $deliveryService->sendViaSMS($this->recipient, $this->ticketData);
                    break;
                case 'dashboard':
                    $deliveryService->sendToDashboard((int)$this->recipient, $this->ticketData);
                    break;
                default:
                    Log::warning("Unknown ticket retry delivery method: {$this->method}");
                    break;
            }
        } catch (\Exception $e) {
            if ($this->attempts < 3) {
                Log::info("Scheduling retry # " . ($this->attempts + 1) . " for delivery failure.");
                dispatch(new self($this->ticketData, $this->method, $this->recipient, $this->attempts + 1))->delay(now()->addMinutes(5));
            } else {
                Log::error("Exceeded maximum delivery retries for recipient {$this->recipient}. Error: " . $e->getMessage());
            }
        }
    }
}

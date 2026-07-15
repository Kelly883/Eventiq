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

    protected array $ticketData;
    protected string $method; // 'email', 'sms', 'dashboard'
    protected string $recipient; // email address, phone number, or user ID

    /**
     * Create a new job instance.
     */
    public function __construct(array $ticketData, string $method, string $recipient)
    {
        $this->ticketData = $ticketData;
        $this->method = $method;
        $this->recipient = $recipient;
    }

    /**
     * Execute the job.
     */
    public function handle(TicketDeliveryService $deliveryService): void
    {
        Log::info("Handling SendTicketDeliveryJob for method: {$this->method}");

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
                Log::warning("Unknown ticket delivery method: {$this->method}");
                break;
        }
    }
}

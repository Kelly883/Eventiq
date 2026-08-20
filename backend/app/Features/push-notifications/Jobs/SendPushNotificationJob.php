<?php

namespace App\Features\PushNotifications\Jobs;

use App\Features\PushNotifications\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Dispatched so push sends don't block the API response (per Step 46's
 * requirement) - runs on QUEUE_CONNECTION (defaults to 'database', see
 * config/queue.php and the jobs table migration).
 */
class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public array $data = [],
    ) {
    }

    public function handle(PushNotificationService $pushNotificationService): void
    {
        $result = $pushNotificationService->sendToUser($this->userId, $this->title, $this->body, $this->data);

        if ($result['sent'] === 0 && $result['failed'] > 0) {
            Log::warning('SendPushNotificationJob: all sends failed', ['user_id' => $this->userId, 'result' => $result]);
        }
    }
}

<?php

namespace App\Features\PushNotifications\Services;

use App\Features\PushNotifications\Models\PushNotificationDevice;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

/**
 * Wraps the Firebase Admin SDK (kreait/firebase-php) for sending push
 * notifications to registered device tokens.
 */
class PushNotificationService
{
    // Nullable: FirebaseServiceProvider binds null (not a Messaging
    // instance) when credentials aren't configured, rather than throwing
    // at boot. Every method here checks isConfigured() before use.
    public function __construct(private ?Messaging $messaging)
    {
    }

    public function isConfigured(): bool
    {
        return $this->messaging !== null;
    }

    /**
     * Send a notification to a single device token.
     *
     * @return bool Whether the send succeeded.
     */
    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('PushNotificationService::sendToToken skipped - Firebase not configured.');

            return false;
        }

        try {
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);

            return true;
        } catch (\Throwable $e) {
            Log::error('PushNotificationService::sendToToken failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Send a notification to every device registered to a user.
     *
     * @return array{sent: int, failed: int}
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): array
    {
        $tokens = PushNotificationDevice::where('user_id', $userId)->pluck('fcm_token');

        $sent = 0;
        $failed = 0;

        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $title, $body, $data)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Register or refresh a device token for a user.
     */
    /**
     * Register or refresh a device token for a user. If $previousToken is
     * given (the frontend detected its token rotated), deletes that stale
     * row rather than leaving a dead token accumulating in the table -
     * otherwise sendToUser() would keep trying (and failing) to send to
     * tokens that no longer exist.
     */
    public function registerDevice(int $userId, string $fcmToken, ?string $platform = null, ?string $previousToken = null): PushNotificationDevice
    {
        if ($previousToken && $previousToken !== $fcmToken) {
            PushNotificationDevice::where('fcm_token', $previousToken)->delete();
        }

        return PushNotificationDevice::updateOrCreate(
            ['fcm_token' => $fcmToken],
            ['user_id' => $userId, 'platform' => $platform, 'last_used_at' => now()]
        );
    }

    public function unregisterDevice(string $fcmToken): void
    {
        PushNotificationDevice::where('fcm_token', $fcmToken)->delete();
    }
}

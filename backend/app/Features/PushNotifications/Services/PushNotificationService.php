<?php

namespace App\Features\PushNotifications\Services;

use App\Features\PushNotifications\Models\PushNotificationDevice;
use App\Features\PushNotifications\Models\PushNotificationHistory;
use App\Features\OfflineSync\Services\OfflineSyncEngine;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class PushNotificationService
{
    public function __construct(private ?Messaging $messaging)
    {
    }

    public function isConfigured(): bool
    {
        return $this->messaging !== null;
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('PushNotificationService::sendToToken skipped - Firebase not configured.');
            return false;
        }

        $device = PushNotificationDevice::where('token_hash', hash('sha256', $token))
            ->first();

        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);

            if ($device) {
                $device->update(['last_used_at' => now()]);
            }

            PushNotificationHistory::create([
                'user_id' => $device->user_id ?? null,
                'device_id' => $device->id ?? null,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'status' => 'sent',
                'sent_at' => now(),
                'gateway_response' => ['provider' => 'firebase'],
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('PushNotificationService::sendToToken failed: ' . $e->getMessage());

            if ($device) {
                $device->recordError($e->getMessage());
            }

            PushNotificationHistory::create([
                'user_id' => $device->user_id ?? null,
                'device_id' => $device->id ?? null,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'status' => 'failed',
                'sent_at' => now(),
                'error_message' => $e->getMessage(),
                'gateway_response' => ['provider' => 'firebase', 'error' => $e->getMessage()],
            ]);

            return false;
        }
    }

    public function sendToUser(string $userId, string $title, string $body, array $data = []): array
    {
        $devices = PushNotificationDevice::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($devices as $device) {
            $token = $device->getDecryptedToken();
            if ($this->sendToToken($token, $title, $body, $data)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'total_devices' => $devices->count()];
    }

    public function registerDevice(string $userId, string $token, string $provider, string $deviceType, ?string $previousToken = null): PushNotificationDevice
    {
        if ($previousToken && $previousToken !== $token) {
            PushNotificationDevice::where('token_hash', hash('sha256', $previousToken))
                ->delete();
            (new OfflineSyncEngine())->purgeDeviceOperations([strtolower($previousToken)]);
        }

        $tokenHash = hash('sha256', $token);

        $existing = PushNotificationDevice::where('token_hash', $tokenHash)
            ->first();

        if ($existing) {
            $existing->update([
                'user_id' => $userId,
                'provider' => $provider,
                'device_type' => $deviceType,
            ]);
            return $existing;
        }

        try {
            $device = PushNotificationDevice::create([
                'user_id' => $userId,
                'provider' => $provider,
                'device_type' => $deviceType,
                'token' => $token,
            ]);

            return $device;
        } catch (UniqueConstraintViolationException $e) {
            $device = PushNotificationDevice::where('token_hash', $tokenHash)
                ->first();

            if ($device) {
                $device->update([
                    'user_id' => $userId,
                    'provider' => $provider,
                    'device_type' => $deviceType,
                ]);
            }

            return $device;
        }
    }

    public function unregisterDevice(string $token): void
    {
        PushNotificationDevice::where('token_hash', hash('sha256', $token))
            ->delete();
        (new OfflineSyncEngine())->purgeDeviceOperations([strtolower($token)]);
    }
}

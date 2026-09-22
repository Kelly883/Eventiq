<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Central alerting for payment-webhook security events.
 *
 * Every anomalous webhook condition (bad signature, failed gateway
 * verification, amount mismatch, refund anomaly, unprocessable payload)
 * goes through here so it is:
 *  - written to the dedicated `webhooks` log channel (own rotated file,
 *    separate from app logs, easy to ship/tail for external alerting)
 *  - mirrored to the default stack at `error` level so it shows up in
 *    normal error monitoring even if the channel is not configured
 *
 * The `alert` type string is a stable identifier intended for log-based
 * alert rules (e.g. trigger on type=signature_verification_failed).
 */
class WebhookAlertService
{
    public function alert(string $type, string $message, array $context = []): void
    {
        $payload = array_merge([
            'alert_type' => 'webhooks-alert',
            'type' => $type,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ], $context);

        Log::channel('webhooks')->error($message, $payload);
        Log::error("[webhook-alert] {$type}: {$message}", $payload);
    }

    /**
     * Non-error conditions worth keeping in the webhook trail
     * (e.g. duplicate events, unknown references) - logged, not alarmed.
     */
    public function notice(string $type, string $message, array $context = []): void
    {
        $payload = array_merge([
            'alert_type' => 'webhooks-notice',
            'type' => $type,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ], $context);

        Log::channel('webhooks')->info($message, $payload);
    }
}
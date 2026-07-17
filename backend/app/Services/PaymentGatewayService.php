<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    public function __construct()
    {
        // Stateless facade; concrete gateway services are selected at runtime.
    }

    protected function gateway(): string
    {
        return (string) (env('PAYMENT_GATEWAY') ?: config('payment.default', 'paystack'));
    }

    /**
     * Initiate an organizer payout.
     *
     * Note: current payment feature services in this codebase only implement
     * payment/refund + webhook verification scaffolding. This method provides
     * orchestration (hold window, max retries, audit logging) and gateway
     * dispatch hooks for future implementation.
     */
    public function initiatePayout(array $payload): array
    {
        $gateway = $this->gateway();

        $holdDays = (int) env('PAYOUT_HOLD_DAYS', 3);
        $auditEnabled = filter_var(env('AUDIT_LOG_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN);

        $payoutReference = $payload['reference'] ?? $this->generateReference($gateway);

        if ($auditEnabled) {
            Log::info('Payout initiation requested', [
                'gateway' => $gateway,
                'reference' => $payoutReference,
                'amount' => $payload['amount'] ?? null,
                'currency' => $payload['currency'] ?? config('payment.currency', 'NGN'),
                'hold_days' => $holdDays,
            ]);
        }

        // Dispatch hook for concrete gateway implementation.
        // Return a normalized response that controllers/jobs can persist.
        return [
            'gateway' => $gateway,
            'reference' => $payoutReference,
            'status' => 'queued',
            'hold_days' => $holdDays,
            'provider_payload' => $payload,
        ];
    }

    /**
     * Get payout status by provider reference.
     */
    public function getPayoutStatus(string $payoutReference): array
    {
        $gateway = $this->gateway();

        // Dispatch hook for concrete gateway implementation.
        return [
            'gateway' => $gateway,
            'reference' => $payoutReference,
            'status' => 'unknown',
        ];
    }

    /**
     * Retry a failed payout.
     */
    public function retryPayout(string $payoutReference, int $attempt = 1, array $payload = []): array
    {
        $gateway = $this->gateway();

        $maxRetries = (int) env('PAYOUT_MAX_RETRIES', 3);
        $auditEnabled = filter_var(env('AUDIT_LOG_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN);

        if ($attempt > $maxRetries) {
            if ($auditEnabled) {
                Log::warning('Payout retry aborted (max retries reached)', [
                    'gateway' => $gateway,
                    'reference' => $payoutReference,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                ]);
            }

            return [
                'gateway' => $gateway,
                'reference' => $payoutReference,
                'status' => 'max_retries_reached',
                'attempt' => $attempt,
            ];
        }

        if ($auditEnabled) {
            Log::info('Payout retry requested', [
                'gateway' => $gateway,
                'reference' => $payoutReference,
                'attempt' => $attempt,
                'payload' => $payload,
            ]);
        }

        // Dispatch hook for concrete gateway implementation.
        return [
            'gateway' => $gateway,
            'reference' => $payoutReference,
            'status' => 'retry_queued',
            'attempt' => $attempt,
        ];
    }

    protected function generateReference(string $gateway): string
    {
        return Str::upper($gateway) . '_PO_' . Str::upper(Str::random(10));
    }
}


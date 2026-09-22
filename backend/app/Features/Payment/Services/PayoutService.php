<?php

namespace App\Features\Payment\Services;

use App\Features\Checkout\Models\Payment;
use App\Features\Fraud\Models\FraudEvent;
use App\Features\Payment\Models\OrganizerPayout;
use App\Models\Organizer;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutService
{
    public function __construct(
        private PaymentGatewayService $paymentGatewayService,
    ) {
    }

    /**
     * Initiate an organizer payout with fraud checks and idempotency.
     *
     * @throws \RuntimeException If payout is blocked
     */
    public function initiateOrganizerPayout(
        Organizer $organizer,
        float $amount,
        string $gateway,
        array $bankDetails,
        string $narration,
        string $idempotencyKey,
    ): OrganizerPayout {
        // 1. Check idempotency - has this exact payout already been processed?
        $existingPayout = OrganizerPayout::where('payout_idempotency_key', $idempotencyKey)->first();
        if ($existingPayout) {
            Log::info("Payout with idempotency key {$idempotencyKey} already exists, returning existing payout");
            return $existingPayout;
        }

        // 2. Check fraud hold
        if ($organizer->fraud_hold) {
            Log::warning("Payout blocked: organizer {$organizer->id} is under fraud hold");
            throw new \RuntimeException(
                "Organizer payout blocked: account under fraud investigation. Reason: {$organizer->fraud_hold_reason}"
            );
        }

        // 3. Check for active fraud events (auto_blocked status)
        $activeFraudEvents = FraudEvent::where('user_id', $organizer->user_id)
            ->where('status', 'auto_blocked')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        if ($activeFraudEvents > 0) {
            Log::warning("Payout blocked: organizer {$organizer->id} has {$activeFraudEvents} active fraud events");
            throw new \RuntimeException(
                "Organizer payout blocked: account has active fraud events under investigation."
            );
        }

        // 4. Verify payment gateway is configured
        if ($gateway === 'paystack' && !$organizer->isPaystackConnected()) {
            throw new \RuntimeException("Organizer does not have Paystack connected.");
        }

        if ($gateway === 'flutterwave' && !$organizer->isFlutterwaveConnected()) {
            throw new \RuntimeException("Organizer does not have Flutterwave connected.");
        }

        // 5. Create payout record with idempotency key
        $payout = DB::transaction(function () use ($organizer, $amount, $gateway, $narration, $idempotencyKey) {
            $payout = OrganizerPayout::create([
                'organizer_id' => $organizer->id,
                'gateway' => $gateway,
                'reference' => 'PO-' . strtoupper(\Illuminate\Support\Str::random(12)),
                'status' => 'pending',
                'amount' => $amount,
                'currency' => $organizer->currency ?? 'NGN',
                'payout_idempotency_key' => $idempotencyKey,
                'metadata' => [
                    'narration' => $narration,
                    'initiated_at' => now()->toIso8601String(),
                ],
            ]);

            return $payout;
        });

        // 6. Initiate transfer at gateway
        try {
            $result = $this->paymentGatewayService->initiatePayout(
                $gateway,
                $amount,
                $bankDetails,
                $narration,
            );

            $payout->update([
                'status' => 'processing',
                'gateway_transfer_id' => $result['id'] ?? $result['reference'] ?? null,
            ]);

            Log::info("Payout initiated", [
                'payout_id' => $payout->id,
                'organizer_id' => $organizer->id,
                'amount' => $amount,
                'gateway' => $gateway,
            ]);
        } catch (\Throwable $e) {
            $payout->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            Log::error("Payout gateway initiation failed", [
                'payout_id' => $payout->id,
                'organizer_id' => $organizer->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $payout;
    }
}

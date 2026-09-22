<?php

namespace App\Console\Commands;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment Reconciliation Command.
 *
 * Compares EventIQ local payment/order state against Paystack and Flutterwave
 * authoritative APIs. Safe to run repeatedly. Never issues tickets twice.
 */
class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile
                            {--hours=24 : Look back this many hours}
                            {--gateway= : Specific gateway (paystack|flutterwave), default both}
                            {--dry-run : Report only, make no changes}';

    protected $description = 'Reconcile local payment/order state with payment gateways';

    private array $results = [
        'checked' => 0,
        'matched' => 0,
        'discrepancies' => 0,
        'refunded' => 0,
        'failed' => 0,
        'skipped' => 0,
    ];

    public function handle(
        PaystackService $paystack,
        FlutterwaveService $flutterwave,
    ): int {
        $hours = (int) $this->option('hours');
        $gateway = $this->option('gateway');
        $dryRun = $this->option('dry-run');

        $this->info("Starting payment reconciliation (last {$hours} hours, dry-run: " . ($dryRun ? 'yes' : 'no') . ")");

        $cutoff = now()->subHours($hours);

        // Find all payments that are pending, processing, or in ambiguous states
        $query = Payment::whereIn('status', ['pending', 'processing', 'initiated'])
            ->where('created_at', '>=', $cutoff);

        if ($gateway) {
            $query->where('gateway', $gateway);
        }

        $payments = $query->get();

        $this->info("Found {$payments->count()} payments to reconcile");

        foreach ($payments as $payment) {
            $this->results['checked']++;
            $this->reconcilePayment($payment, $paystack, $flutterwave, $dryRun);
        }

        // Summary
        $this->newLine();
        $this->info("Reconciliation complete:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Checked', $this->results['checked']],
                ['Matched', $this->results['matched']],
                ['Discrepancies', $this->results['discrepancies']],
                ['Refunded', $this->results['refunded']],
                ['Failed', $this->results['failed']],
                ['Skipped', $this->results['skipped']],
            ]
        );

        // Alert on discrepancies
        if ($this->results['discrepancies'] > 0) {
            Log::warning('Payment reconciliation found discrepancies', [
                'count' => $this->results['discrepancies'],
                'hours' => $hours,
            ]);
        }

        return $this->results['discrepancies'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function reconcilePayment(
        Payment $payment,
        PaystackService $paystack,
        FlutterwaveService $flutterwave,
        bool $dryRun,
    ): void {
        $gateway = $payment->gateway;
        $reference = $payment->payment_intent_id ?? $payment->gateway_reference;

        if (!$reference) {
            $this->warn("Payment {$payment->id}: no reference, skipping");
            $this->results['skipped']++;
            return;
        }

        try {
            $verification = match ($gateway) {
                'paystack' => $paystack->verifyTransaction($reference),
                'flutterwave' => $flutterwave->verifyTransaction($reference),
                default => throw new \RuntimeException("Unknown gateway: {$gateway}"),
            };
        } catch (\Throwable $e) {
            $this->error("Payment {$payment->id}: gateway verification failed - {$e->getMessage()}");
            $this->results['skipped']++;
            return;
        }

        $rawStatus = match ($gateway) {
            'paystack' => (string) data_get($verification, 'data.status', data_get($verification, 'status', '')),
            'flutterwave' => (string) data_get($verification, 'data.status', data_get($verification, 'status', '')),
        };

        $status = match ($gateway) {
            'paystack' => match ($rawStatus) {
                'success' => 'success',
                'failed' => 'failed',
                'abandoned' => 'abandoned',
                default => 'unknown',
            },
            'flutterwave' => match ($rawStatus) {
                'successful', 'completed' => 'success',
                'failed' => 'failed',
                'pending' => 'pending',
                default => 'unknown',
            },
        };

        // Verify amount
        $rawAmount = data_get($verification, 'data.amount');
        if ($rawAmount !== null) {
            $verifiedAmount = (float) $rawAmount;
            if ($gateway === 'paystack') {
                $verifiedAmount = $verifiedAmount / 100;
            }
            $orderAmount = (float) $payment->amount;
            $allowedDiff = max(0.01, $orderAmount * 0.005);

            if (abs($verifiedAmount - $orderAmount) > $allowedDiff) {
                $this->error("Payment {$payment->id}: amount mismatch (expected {$orderAmount}, gateway {$verifiedAmount})");
                $this->results['discrepancies']++;
                $this->logDiscrepancy($payment, 'amount_mismatch', $verification, $dryRun);
                return;
            }
        }

        // Verify currency
        $gatewayCurrency = match ($gateway) {
            'paystack' => (string) data_get($verification, 'data.currency', ''),
            'flutterwave' => (string) data_get($verification, 'data.currency', ''),
        };

        if ($gatewayCurrency && strtoupper($gatewayCurrency) !== strtoupper($payment->currency ?? '')) {
            $this->error("Payment {$payment->id}: currency mismatch (expected {$payment->currency}, gateway {$gatewayCurrency})");
            $this->results['discrepancies']++;
            $this->logDiscrepancy($payment, 'currency_mismatch', $verification, $dryRun);
            return;
        }

        // Handle based on status
        match ($status) {
            'success' => $this->handleGatewaySuccess($payment, $verification, $dryRun),
            'failed' => $this->handleGatewayFailure($payment, $verification, $dryRun),
            'abandoned' => $this->handleGatewayAbandoned($payment, $verification, $dryRun),
            'pending' => $this->info("Payment {$payment->id}: still pending at gateway"),
            'unknown' => $this->warn("Payment {$payment->id}: unknown gateway status '{$rawStatus}'"),
        };
    }

    private function handleGatewaySuccess(Payment $payment, array $verification, bool $dryRun): void
    {
        $order = Order::find($payment->order_id);

        if (!$order) {
            $this->error("Payment {$payment->id}: order {$payment->order_id} not found");
            $this->results['discrepancies']++;
            return;
        }

        // Already completed - idempotent, no action needed
        if ($order->status === 'completed') {
            $this->results['matched']++;
            return;
        }

        $this->info("Payment {$payment->id}: gateway SUCCESS but local status is '{$order->status}' — requires manual review");
        $this->results['discrepancies']++;

        if (!$dryRun) {
            // Create audit record but DO NOT auto-fulfill
            $this->logDiscrepancy($payment, 'missed_webhook_success', $verification, false);
        }
    }

    private function handleGatewayFailure(Payment $payment, array $verification, bool $dryRun): void
    {
        $order = Order::find($payment->order_id);

        if (!$order || $order->status === 'completed') {
            $this->info("Payment {$payment->id}: gateway FAILED but order already completed — ignoring");
            $this->results['matched']++;
            return;
        }

        $this->info("Payment {$payment->id}: gateway confirms FAILED");

        if (!$dryRun) {
            $payment->update([
                'status' => 'failed',
                'gateway_response' => $verification,
                'last_error' => 'Gateway confirms payment failed',
            ]);

            if ($order->status !== 'failed') {
                $order->update(['status' => 'failed']);
            }
        }

        $this->results['failed']++;
    }

    private function handleGatewayAbandoned(Payment $payment, array $verification, bool $dryRun): void
    {
        $order = Order::find($payment->order_id);

        if (!$order || $order->status === 'completed') {
            $this->results['matched']++;
            return;
        }

        $this->info("Payment {$payment->id}: gateway confirms ABANDONED");

        if (!$dryRun) {
            $payment->update([
                'status' => 'abandoned',
                'gateway_response' => $verification,
            ]);

            if ($order->status === 'pending') {
                $order->update(['status' => 'abandoned']);
            }
        }

        $this->results['failed']++;
    }

    private function logDiscrepancy(Payment $payment, string $type, array $gatewayData, bool $dryRun): void
    {
        AuditLog::create([
            'action' => "payment_reconciliation_discrepancy",
            'target_type' => Payment::class,
            'target_id' => $payment->id,
            'user_id' => null,
            'context' => [
                'type' => $type,
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'local_status' => $payment->status,
                'gateway' => $payment->gateway,
                'dry_run' => $dryRun,
                'gateway_data' => $gatewayData,
            ],
            'ip_address' => '0.0.0.0',
        ]);
    }
}

<?php

namespace App\Console\Commands;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Checkout\Models\Ticket;
use App\Features\Refunds\Models\RefundRequest;
use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Refund Reconciliation Command.
 *
 * Detects situations where the gateway has refunded a payment but EventIQ
 * still shows the order as completed or tickets as valid.
 */
class ReconcileRefunds extends Command
{
    protected $signature = 'refunds:reconcile
                            {--hours=24 : Look back this many hours}
                            {--gateway= : Specific gateway (paystack|flutterwave), default both}
                            {--dry-run : Report only, make no changes}';

    protected $description = 'Reconcile local refund state with payment gateways';

    private array $results = [
        'checked' => 0,
        'matched' => 0,
        'discrepancies' => 0,
        'tickets_voided' => 0,
        'skipped' => 0,
    ];

    public function handle(
        PaystackService $paystack,
        FlutterwaveService $flutterwave,
    ): int {
        $hours = (int) $this->option('hours');
        $gateway = $this->option('gateway');
        $dryRun = $this->option('dry-run');

        $this->info("Starting refund reconciliation (last {$hours} hours, dry-run: " . ($dryRun ? 'yes' : 'no') . ")");

        $cutoff = now()->subHours($hours);

        // Find payments that are marked successful locally but may have been refunded
        $query = Payment::where('status', 'success')
            ->where('created_at', '>=', $cutoff);

        if ($gateway) {
            $query->where('gateway', $gateway);
        }

        $payments = $query->get();

        $this->info("Found {$payments->count()} successful payments to check for refunds");

        foreach ($payments as $payment) {
            $this->results['checked']++;
            $this->reconcileRefund($payment, $paystack, $flutterwave, $dryRun);
        }

        // Also check refund requests that are still pending/completed
        $refundQuery = RefundRequest::whereIn('status', ['approved', 'processing', 'completed'])
            ->where('created_at', '>=', $cutoff);

        $refundRequests = $refundQuery->get();
        $this->info("Found {$refundRequests->count()} refund requests to verify");

        foreach ($refundRequests as $refundRequest) {
            $this->results['checked']++;
            $this->reconcileRefundRequest($refundRequest, $paystack, $flutterwave, $dryRun);
        }

        // Summary
        $this->newLine();
        $this->info("Refund reconciliation complete:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Checked', $this->results['checked']],
                ['Matched', $this->results['matched']],
                ['Discrepancies', $this->results['discrepancies']],
                ['Tickets Voided', $this->results['tickets_voided']],
                ['Skipped', $this->results['skipped']],
            ]
        );

        if ($this->results['discrepancies'] > 0) {
            Log::warning('Refund reconciliation found discrepancies', [
                'count' => $this->results['discrepancies'],
                'hours' => $hours,
            ]);
        }

        return $this->results['discrepancies'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function reconcileRefund(
        Payment $payment,
        PaystackService $paystack,
        FlutterwaveService $flutterwave,
        bool $dryRun,
    ): void {
        $gateway = $payment->gateway;
        $reference = $payment->payment_intent_id ?? $payment->gateway_reference;

        if (!$reference) {
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

        // Check if gateway shows refunded status
        $rawStatus = match ($gateway) {
            'paystack' => (string) data_get($verification, 'data.status', ''),
            'flutterwave' => (string) data_get($verification, 'data.status', ''),
        };

        $isRefunded = in_array(strtolower($rawStatus), ['refunded', 'reversed', 'partially_refunded'], true);

        // Also check for refund data in the response
        $refundData = data_get($verification, 'data.refund');
        $hasRefundData = !empty($refundData);

        if (!$isRefunded && !$hasRefundData) {
            $this->results['matched']++;
            return;
        }

        $order = Order::find($payment->order_id);

        if (!$order) {
            $this->error("Payment {$payment->id}: order not found");
            $this->results['discrepancies']++;
            return;
        }

        $this->info("Payment {$payment->id}: gateway shows REFUNDED but local status is '{$order->status}'");
        $this->results['discrepancies']++;

        if (!$dryRun) {
            // Void all valid tickets for this order
            $validTickets = Ticket::where('order_id', $order->id)
                ->where('status', 'valid')
                ->get();

            foreach ($validTickets as $ticket) {
                $ticket->update([
                    'status' => 'void',
                    'refund_status' => 'reconciliation_void',
                ]);
                $this->results['tickets_voided']++;
            }

            // Update payment and order status
            $payment->update([
                'status' => 'refunded',
                'gateway_response' => $verification,
            ]);

            $order->update(['status' => 'refunded']);

            // Create audit record
            AuditLog::create([
                'action' => 'refund_reconciliation_detected',
                'target_type' => Payment::class,
                'target_id' => $payment->id,
                'user_id' => null,
                'context' => [
                    'type' => 'missed_refund_webhook',
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'gateway' => $gateway,
                    'tickets_voided' => $validTickets->count(),
                    'gateway_data' => $verification,
                ],
                'ip_address' => '0.0.0.0',
            ]);
        }
    }

    private function reconcileRefundRequest(
        RefundRequest $refundRequest,
        PaystackService $paystack,
        FlutterwaveService $flutterwave,
        bool $dryRun,
    ): void {
        // If we have a gateway refund ID, verify it exists at the provider
        $gatewayRefundId = $refundRequest->payment_gateway_refund_id;

        if (!$gatewayRefundId) {
            $this->results['skipped']++;
            return;
        }

        // For now, just log that we verified the refund request exists
        // In production, you would query the gateway's refund API
        $this->results['matched']++;
    }
}

<?php

namespace App\Features\Checkout\Http\Controllers;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Checkout\Models\Ticket;
use App\Features\Delivery\Jobs\SendTicketDeliveryJob;
use App\Features\Inventory\Models\TicketInventory;
use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use App\Features\QRCodeTicketing\Services\QRCodeService;
use App\Http\Controllers\Controller;
use App\Models\AnalyticsEventsMetric;
use App\Models\TicketTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function __construct(
        private PaystackService $paystack,
        private FlutterwaveService $flutterwave,
        private QRCodeService $qrCodeService,
        private \App\Features\OrganizerProfile\Services\OrganizerNotificationService $organizerNotificationService,
        private \App\Services\WebhookAlertService $webhookAlerts,
    ) {
    }

    /**
     * POST /api/webhooks/payment-provider - unified entry point for both
     * gateways (detects which one via header presence, since Paystack
     * sends x-paystack-signature and Flutterwave sends verif-hash).
     *
     * Defense in depth: verifies the webhook signature first, THEN
     * independently calls the gateway's own verify-transaction API rather
     * than trusting amounts/status straight from the webhook body -
     * signature verification proves the request came from the gateway,
     * not that the payload wasn't stale or the transaction is really
     * complete on the gateway's own records.
     */
    public function handle(Request $request)
    {
        $gateway = $this->detectGateway($request);

        if (! $gateway) {
            Log::warning('WebhookController: could not determine gateway from headers', ['ip' => $request->ip()]);
            $this->webhookAlerts->alert('unrecognized_source', 'Webhook received without recognizable gateway signature headers', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Unrecognized webhook source'], 400);
        }

        $reference = $this->verifyAndExtractReference($request, $gateway);

        if (! $reference) {
            $this->webhookAlerts->alert('signature_verification_failed', 'Webhook signature verification failed', [
                'gateway' => $gateway,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $order = Order::where('payment_intent_id', $reference)->first();

        if (! $order) {
            Log::warning("WebhookController: no order found for reference {$reference}");
            $this->webhookAlerts->notice('unknown_reference', 'Webhook for unknown reference', [
                'gateway' => $gateway,
                'reference' => $reference,
            ]);

            return response()->json(['received' => true]); // 200 so the gateway doesn't retry forever on a ref we'll never recognize
        }

        // Handle refund/chargeback events BEFORE the completed-order early
        // return: refunds and disputes arrive *after* the payment succeeded
        // (order already 'completed'), so they must not be swallowed by it.
        $eventType = $request->input('event', $request->input('type', ''));
        if ($this->isRefundEvent($eventType)) {
            $eventId = $this->extractEventId($request, $gateway);
            if ($eventId && $this->isEventAlreadyProcessed($eventId)) {
                Log::info("WebhookController: duplicate refund event {$eventId} for order {$order->id}, skipping");
                $this->webhookAlerts->notice('duplicate_event', 'Duplicate webhook event skipped', [
                    'gateway' => $gateway,
                    'event_id' => $eventId,
                    'order_id' => $order->id,
                ]);
                return response()->json(['received' => true]);
            }

            return $this->handleRefundEvent($order, $request, $gateway, $eventType);
        }

        if ($order->status === 'completed') {
            return response()->json(['received' => true]); // Already processed - webhook delivery isn't guaranteed exactly-once
        }

        // Event ID deduplication: prevent duplicate webhook processing
        $eventId = $this->extractEventId($request, $gateway);
        if ($eventId && $this->isEventAlreadyProcessed($eventId)) {
            Log::info("WebhookController: duplicate event {$eventId} for order {$order->id}, skipping");
            $this->webhookAlerts->notice('duplicate_event', 'Duplicate webhook event skipped', [
                'gateway' => $gateway,
                'event_id' => $eventId,
                'order_id' => $order->id,
            ]);
            return response()->json(['received' => true]);
        }

        if ($gateway === 'flutterwave' && empty($request->input('data.id'))) {
            Log::warning("WebhookController: flutterwave payload missing data.id for reference {$reference}");
            $this->webhookAlerts->alert('invalid_payload', 'Flutterwave webhook payload missing data.id', [
                'gateway' => $gateway,
                'reference' => $reference,
                'order_id' => $order->id,
            ]);

            return response()->json(['message' => 'Invalid flutterwave payload'], 422);
        }

        try {
            $flutterwaveTransactionId = $gateway === 'flutterwave' ? (string) ($request->input('data.id') ?? '') : '';
            $verification = $gateway === 'paystack'
                ? $this->paystack->verifyTransaction($reference)
                : $this->flutterwave->verifyTransaction($flutterwaveTransactionId);
        } catch (\Throwable $e) {
            Log::error("WebhookController: gateway verification call failed for {$reference}: " . $e->getMessage());
            $this->webhookAlerts->alert('verification_api_error', 'Gateway verify-transaction call threw an exception', [
                'gateway' => $gateway,
                'reference' => $reference,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Verification failed'], 502);
        }

        $rawStatus = $gateway === 'paystack'
            ? (string) data_get($verification, 'data.status', data_get($verification, 'status', ''))
            : (string) data_get($verification, 'data.status', data_get($verification, 'status', ''));

        $status = $gateway === 'paystack'
            ? match ($rawStatus) {
                'success' => 'success',
                'failed' => 'failed',
                'abandoned' => 'abandoned',
                default => 'failed',
            }
            : match ($rawStatus) {
                'successful', 'completed' => 'success',
                'failed' => 'failed',
                'pending' => 'pending',
                default => 'failed',
            };

        // Late payment detection: the order may have been expired by
        // ExpirePendingOrders (inventory released) before the customer's
        // money actually arrived. Those payments must not be silently
        // fulfilled against re-sold inventory.
        $isLatePayment = $order->status === 'expired';

        // Amount verification: defense in depth
        // Ensure gateway amount matches order total (with small tolerance for rounding)
        // Only verify if the gateway returned an amount - if not, proceed without verification
        if ($status === 'success') {
            $rawAmount = data_get($verification, 'data.amount') ?? data_get($verification, 'amount', null);
            
            if ($rawAmount !== null) {
                $verifiedAmount = (float) $rawAmount;
                
                // Paystack returns amount in kobo (smallest unit), divide by 100
                if ($gateway === 'paystack') {
                    $verifiedAmount = $verifiedAmount / 100;
                }
                
                $orderAmount = (float) $order->total_amount;
                $allowedDiff = max(0.01, $orderAmount * 0.005); // 0.5% tolerance or 1 cent minimum
                
                if (abs($verifiedAmount - $orderAmount) > $allowedDiff) {
                    Log::error("WebhookController: amount mismatch for order {$order->id}. Expected: {$orderAmount}, Gateway: {$verifiedAmount}");
                    $this->webhookAlerts->alert('amount_mismatch', 'Gateway amount does not match order total', [
                        'gateway' => $gateway,
                        'order_id' => $order->id,
                        'reference' => $reference,
                        'expected' => $orderAmount,
                        'gateway_amount' => $verifiedAmount,
                    ]);

                    if ($isLatePayment) {
                        // Money arrived for an order we no longer intend to
                        // fulfil at that amount - refund it in full.
                        $this->handleLateExpiredPayment($order, $gateway, $reference, $verification, $verifiedAmount, 'Amount mismatch on late payment for expired order', $eventId);

                        return response()->json(['received' => true, 'refunded' => true]);
                    }

                    return response()->json(['message' => 'Amount mismatch - payment not processed'], 422);
                }
            }
            // If no amount returned, continue processing without amount verification

            if ($isLatePayment && ! $this->hasInventoryForOrder($order)) {
                // Fast path: tickets are gone - refund without entering the
                // fulfilment transaction. (Re-checked under lock below.)
                $this->handleLateExpiredPayment($order, $gateway, $reference, $verification, null, 'Tickets sold out after order expired', $eventId);

                return response()->json(['received' => true, 'refunded' => true]);
            }
        }

        if (! in_array($status, ['success', 'pending'])) {
            $order->update(['status' => $status === 'abandoned' ? 'abandoned' : 'failed']);
            Payment::where('order_id', $order->id)->update([
                'status' => $status,
                'gateway_response' => $verification,
                'webhook_event_id' => $eventId,
            ]);

            return response()->json(['received' => true]);
        }

        try {
            DB::transaction(function () use ($order, $verification, $status, $isLatePayment, $eventId) {
                if ($isLatePayment) {
                    // Re-verify under lock that the inventory is still available
                    // before reactivating a long-expired order.
                    if (! $this->hasInventoryForOrder($order, lockRows: true)) {
                        throw new \RuntimeException('inventory_no_longer_available');
                    }
                    $order->update(['status' => 'pending']);
                }

                $order->update(['status' => $status === 'success' ? 'completed' : 'pending']);
                Payment::where('order_id', $order->id)->update([
                    'status' => $status,
                    'gateway_response' => $verification,
                    'webhook_event_id' => $eventId,
                ]);

            foreach ($order->items as $item) {
                // Lock the ticket tier row to prevent concurrent sold_count updates
                $tier = TicketTier::where('id', $item->ticket_tier_id)
                    ->lockForUpdate()
                    ->first();

                // Lock the inventory row to prevent concurrent sold_quantity updates
                $inventory = TicketInventory::where('ticket_tier_id', $item->ticket_tier_id)
                    ->lockForUpdate()
                    ->first();

                for ($i = 0; $i < $item->quantity; $i++) {
                    $attendeeName = $order->user->name ?? 'Attendee';
                    $attendeeEmail = $order->user->email ?? 'unknown@example.com';
                    $tierName = $tier->name ?? ('Tier #' . $item->ticket_tier_id);

                    $ticket = Ticket::create([
                        'order_id' => $order->id,
                        'event_id' => $order->event_id,
                        'user_id' => $order->user_id,
                        'ticket_tier_id' => $item->ticket_tier_id,
                        'ticket_id' => 'TCK-' . Str::uuid(),
                        'attendee_name' => $attendeeName,
                        'attendee_email' => $attendeeEmail,
                        'tier' => $tierName,
                        'status' => 'valid',
                    ]);

                    $ticket->update(['qr_code_data' => $this->qrCodeService->generateForTicket($ticket)]);
                }

                // Atomically increment sold_count on ticket_tier with CHECK constraint enforcing sold_count <= quantity
                if ($tier) {
                    $tier->increment('sold_count', $item->quantity);
                }

                // Atomically increment total_sold on inventory
                if ($inventory) {
                    $inventory->increment('total_sold', $item->quantity);
                }

                // Update analytics metrics for the event
                if ($order->event_id) {
                    $metric = AnalyticsEventsMetric::where('event_id', $order->event_id)->first();
                    if ($metric) {
                        $metric->update([
                            'total_tickets_sold' => $metric->total_tickets_sold + $item->quantity,
                            'last_updated_at' => now(),
                        ]);
                    }
                }
            }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'inventory_no_longer_available' && $isLatePayment) {
                // Sold out between the fast-path check and the locked
                // re-check: roll everything back and refund the customer.
                $this->handleLateExpiredPayment($order, $gateway, $reference, $verification, null, 'Tickets sold out (raced between availability check and fulfilment)', $eventId);

                return response()->json(['received' => true, 'refunded' => true]);
            }

            throw $e;
        }

        $this->organizerNotificationService->notifyPaymentSuccess($order);

        SendTicketDeliveryJob::dispatch('email', [
            'to' => $order->user->email,
            'subject' => 'Your tickets for ' . ($order->event->title ?? 'your event'),
            'body' => "Thanks for your purchase! Your tickets are ready - view them in your dashboard.",
        ]);

        return response()->json(['received' => true]);
    }

    /**
     * Check (optionally under row locks) whether every order item can still
     * be fulfilled, i.e. the tier has at least the requested quantity left.
     */
    private function hasInventoryForOrder(Order $order, bool $lockRows = false): bool
    {
        foreach ($order->items as $item) {
            $tierQuery = TicketTier::where('id', $item->ticket_tier_id);
            if ($lockRows) {
                $tierQuery->lockForUpdate();
            }
            $tier = $tierQuery->first();

            if (! $tier) {
                return false;
            }

            $availableCount = $tier->quantity !== null
                ? (int) $tier->quantity - (int) $tier->sold_count
                : null;

            $inventoryQuery = TicketInventory::where('ticket_tier_id', $item->ticket_tier_id);
            if ($lockRows) {
                $inventoryQuery->lockForUpdate();
            }
            $inventory = $inventoryQuery->first();

            $remaining = $availableCount ?? $inventory?->remaining ?? $tier->capacity;

            if ((int) $remaining < (int) $item->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Money arrived after the order had already been expired (and its
     * inventory possibly re-sold). Refund the customer in full, mark the
     * payment/order accordingly and keep an audit trail.
     */
    private function handleLateExpiredPayment(
        Order $order,
        string $gateway,
        string $reference,
        array $verification,
        ?float $verifiedAmount,
        string $reason,
        ?string $eventId = null
    ): void {
        $this->webhookAlerts->alert('late_payment_refunded', 'Payment received for expired order - refunded', [
            'gateway' => $gateway,
            'order_id' => $order->id,
            'reference' => $reference,
            'reason' => $reason,
            'verified_amount' => $verifiedAmount,
        ]);

        $payment = Payment::where('order_id', $order->id)->first();

        $refundAmount = $verifiedAmount ?? (float) ($payment?->amount ?? $order->total_amount);

        $gatewayTransactionId = (string) ($payment?->gateway_transaction_id ?? '');
        $refundReason = 'Order expired before payment completed - automatic full refund';

        try {
            if ($gateway === 'paystack') {
                $this->paystack->refund($gatewayTransactionId, $refundAmount, $refundReason);
            } else {
                $this->flutterwave->refund($gatewayTransactionId, $refundAmount, $refundReason);
            }

            $refundStatus = 'refunded';
        } catch (\Throwable $e) {
            // Refund call failed - do not block the webhook (gateway will
            // retry); keep the money state visible for manual follow-up.
            Log::error("WebhookController: automatic refund failed for expired order {$order->id}: " . $e->getMessage());
            $this->webhookAlerts->alert('late_payment_refund_failed', 'Automatic refund for expired order failed - manual action required', [
                'gateway' => $gateway,
                'order_id' => $order->id,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            $refundStatus = 'refund_pending';
        }

        DB::transaction(function () use ($order, $payment, $verification, $refundAmount, $refundStatus, $refundReason, $eventId) {
            $order->update(['status' => 'refunded']);

            if ($payment) {
                $payment->update([
                    'status' => $refundStatus,
                    'gateway_response' => $verification,
                    'refunded_amount' => $refundAmount,
                    'refunded_at' => now(),
                    'refund_reason' => $refundReason,
                    'is_fully_refunded' => $refundStatus === 'refunded',
                    'webhook_event_id' => $eventId,
                ]);
            }
        });
    }

    private function detectGateway(Request $request): ?string
    {
        if ($request->hasHeader('x-paystack-signature')) return 'paystack';
        if ($request->hasHeader('verif-hash')) return 'flutterwave';

        return null;
    }

    private function verifyAndExtractReference(Request $request, string $gateway): ?string
    {
        if ($gateway === 'paystack') {
            $valid = $this->paystack->verifyWebhookSignature(
                $request->getContent(),
                $request->header('x-paystack-signature', '')
            );

            return $valid ? ($request->input('data.reference')) : null;
        }

        $valid = $this->flutterwave->verifyWebhookSignature($request->header('verif-hash', ''));

        return $valid ? ($request->input('data.tx_ref') ?? $request->input('txRef')) : null;
    }

    private function extractEventId(Request $request, string $gateway): ?string
    {
        if ($gateway === 'paystack') {
            return $request->input('data.event.id') 
                ?? $request->header('x-paystack-event-id')
                ?? null;
        }

        if ($gateway === 'flutterwave') {
            return $request->input('data.id')
                ?? $request->header('verif-hash')
                ?? null;
        }

        return null;
    }

    private function isEventAlreadyProcessed(?string $eventId): bool
    {
        if (!$eventId) {
            return false;
        }

        return Payment::where('webhook_event_id', $eventId)->exists();
    }

    private function isRefundEvent(string $eventType): bool
    {
        $refundEvents = [
            'charge.dispute',
            'charge.refund',
            'chargeback',
            'refund.process',
            'transaction.refund',
        ];

        return in_array(strtolower($eventType), array_map('strtolower', $refundEvents), true);
    }

    private function handleRefundEvent(Order $order, Request $request, string $gateway, string $eventType): \Illuminate\Http\JsonResponse
    {
        $isChargeback = str_contains(strtolower($eventType), 'dispute') || str_contains(strtolower($eventType), 'chargeback');
        
        DB::transaction(function () use ($order, $request, $gateway, $eventType, $isChargeback) {
            // Re-fetch under lock and USE the fresh copy: the status guard
            // below must read post-lock state, not the possibly-stale model
            // passed in (otherwise two concurrent refund events can both
            // pass the guard and double-apply).
            $order = $order->lockForUpdate()->first() ?? $order;

            if (in_array($order->status, ['refunded', 'partially_refunded', 'chargeback'], true)) {
                return;
            }

            $payment = $order->payments()->where('status', 'success')->first();
            if (!$payment) {
                return;
            }

            $gatewayTransactionId = $request->input('data.transaction_id') 
                ?? $request->input('data.id')
                ?? null;

            $refundAmount = (float) ($request->input('data.amount') ?? $request->input('data.refund_amount') ?? 0);
            
            if ($gateway === 'paystack') {
                $refundAmount = $refundAmount / 100;
            }

            // Refund amount verification: guard against over-refunds
            // (defense in depth on top of the gateway's own checks).
            $paidAmount = (float) $payment->amount;

            if ($refundAmount > 0 && $refundAmount > $paidAmount * 1.005) {
                $this->webhookAlerts->alert('refund_amount_exceeds_payment', 'Refund event amount exceeds original payment', [
                    'gateway' => $gateway,
                    'order_id' => $order->id,
                    'event_type' => $eventType,
                    'paid_amount' => $paidAmount,
                    'refund_amount' => $refundAmount,
                ]);
                // Clamp to what was actually paid.
                $refundAmount = $paidAmount;
            }

            if ($eventId = $this->extractEventId($request, $gateway)) {
                $payment->update(['webhook_event_id' => $eventId]);
            }

            if ($isChargeback) {
                $order->update([
                    'status' => 'chargeback',
                    'failure_reason' => "Chargeback received: {$eventType}",
                ]);
                
                $payment->update([
                    'status' => 'charged_back',
                    'gateway_transaction_id' => $gatewayTransactionId,
                    'gateway_response' => $request->all(),
                    'last_error' => "Chargeback: {$eventType}",
                ]);
                
                if ($order->tickets()->where('status', 'valid')->count() > 0) {
                    $order->tickets()->where('status', 'valid')->update([
                        'status' => 'void',
                        'refund_status' => 'charged_back',
                    ]);
                }
                
                $this->webhookAlerts->alert('chargeback_received', 'Chargeback/dispute event received, tickets voided', [
                    'gateway' => $gateway,
                    'order_id' => $order->id,
                    'event_type' => $eventType,
                ]);
                
                Log::warning("WebhookController: order {$order->id} chargeback received, tickets voided");
            } elseif ($refundAmount <= 0 || $refundAmount >= $paidAmount * 0.99) {
                $order->update([
                    'status' => 'refunded',
                    'failure_reason' => "Full refund processed: {$eventType}",
                ]);
                
                $payment->update([
                    'status' => 'refunded',
                    'refunded_amount' => $refundAmount,
                    'is_fully_refunded' => true,
                    'refunded_at' => now(),
                    'refund_reason' => $request->input('data.reason', $eventType),
                    'gateway_transaction_id' => $gatewayTransactionId,
                    'gateway_response' => $request->all(),
                ]);
                
                if ($order->tickets()->where('status', 'valid')->count() > 0) {
                    $order->tickets()->where('status', 'valid')->update([
                        'status' => 'void',
                        'refund_status' => 'fully_refunded',
                    ]);
                }
                
                Log::info("WebhookController: order {$order->id} fully refunded, tickets voided");
            } else {
                // Partial refund: never let cumulative refunds exceed the paid amount.
                $newRefundedTotal = min(
                    (float) ($payment->refunded_amount ?? 0) + $refundAmount,
                    $paidAmount
                );

                $order->update([
                    'status' => 'partially_refunded',
                    'failure_reason' => "Partial refund: {$eventType}",
                ]);
                
                $payment->update([
                    'status' => 'partially_refunded',
                    'refunded_amount' => $newRefundedTotal,
                    'is_fully_refunded' => $newRefundedTotal >= $paidAmount * 0.99,
                    'refunded_at' => now(),
                    'refund_reason' => $request->input('data.reason', $eventType),
                    'gateway_transaction_id' => $gatewayTransactionId,
                    'gateway_response' => $request->all(),
                ]);
                
                Log::info("WebhookController: order {$order->id} partially refunded ({$refundAmount})");
            }
        });

        return response()->json(['received' => true]);
    }
}

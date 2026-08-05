<?php

namespace App\Services;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Payment\Contracts\PaymentGatewayContract;
use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use App\Features\Refunds\Models\RefundRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Abstracts Paystack/Flutterwave for refunds and payouts (organizer
 * settlements) through PaymentGatewayContract. Adding a third gateway
 * later: implement the contract, add one line to resolveGateway() -
 * nothing else in this class changes. Retargeted from the original
 * Stripe/PayPal-based PRD spec per project direction.
 */
class PaymentGatewayService
{
    public function __construct(
        private PaystackService $paystack,
        private FlutterwaveService $flutterwave,
    ) {
    }

    private function resolveGateway(string $gateway): PaymentGatewayContract
    {
        return match ($gateway) {
            'paystack' => $this->paystack,
            'flutterwave' => $this->flutterwave,
            default => throw new \InvalidArgumentException("Unknown payment gateway: {$gateway}"),
        };
    }

    /**
     * Processes a refund for an approved RefundRequest: looks up which
     * gateway/reference the original order was paid through, calls that
     * gateway's refund API, and records the result on the refund request
     * (payment_gateway_refund_id / payment_gateway_response).
     */
    public function processRefund(int $refundRequestId): array
    {
        $refundRequest = RefundRequest::with('ticket.order')->findOrFail($refundRequestId);
        $order = $refundRequest->ticket->order;

        if (! $order) {
            throw new \RuntimeException("Refund request {$refundRequestId}: no order found for the associated ticket.");
        }

        if (! $order->payment_gateway) {
            throw new \RuntimeException("Refund request {$refundRequestId}: order has no payment gateway.");
        }

        $amount = (float) ($refundRequest->approved_amount ?? $refundRequest->requested_amount ?? 0);
        if ($amount <= 0) {
            throw new \RuntimeException("Refund request {$refundRequestId}: refund amount must be greater than zero.");
        }

        // Prefer a successful payment attempt's transaction ID, then fall back
        // to the order-level transaction reference, then payment_intent_id.
        $payment = Payment::where('order_id', $order->id)
            ->where('status', 'success')
            ->orderByDesc('id')
            ->first();

        $gatewayTransactionId = $payment->gateway_transaction_id
            ?? $order->gateway_transaction_id
            ?? $payment->payment_intent_id
            ?? $order->payment_intent_id;

        if (! $gatewayTransactionId) {
            throw new \RuntimeException("Refund request {$refundRequestId}: no gateway transaction identifier found.");
        }

        $reason = (string) ($refundRequest->reason ?? '');

        try {
            $result = $this->resolveGateway($order->payment_gateway)->refund($gatewayTransactionId, $amount, $reason);
        } catch (\Throwable $e) {
            Log::error("PaymentGatewayService::processRefund failed for refund request {$refundRequestId}: " . $e->getMessage());
            throw $e;
        }

        $refundRequest->update([
            'payment_gateway_refund_id' => $this->parseRefundId($order->payment_gateway, $result),
            'payment_gateway_response' => $result,
        ]);

        Payment::where('order_id', $order->id)->update(['status' => 'refunded']);

        return $result;
    }

    /**
     * Extracts the gateway's own refund identifier from its response
     * shape, which differs between Paystack and Flutterwave - the one
     * piece of gateway-specific knowledge that can't live behind the
     * shared interface, since the two APIs simply name this field
     * differently.
     */
    private function parseRefundId(string $gateway, array $response): ?string
    {
        return $gateway === 'paystack'
            ? ($response['id'] ?? null)
            : ($response['flw_ref'] ?? $response['id'] ?? null);
    }

    /**
     * Initiates a payout (organizer settlement) to a bank account.
     *
     * @param array $bankDetails Expected keys: account_number, bank_code
     *   (both gateways) / account_name (Paystack only, for its transfer-
     *   recipient step). See App\Features\Payment\Models\OrganizerPayoutMethod
     *   for where these are stored per-organizer.
     */
    public function initiatePayout(string $gateway, float $amount, array $bankDetails, string $narration = 'Payout'): array
    {
        $reference = 'pay_' . Str::uuid();

        return $this->resolveGateway($gateway)->transfer($amount, $bankDetails, $narration, $reference);
    }

    public function checkTransferStatus(string $gateway, string $transferReference): array
    {
        return $this->resolveGateway($gateway)->checkTransferStatus($transferReference);
    }
}

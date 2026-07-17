<?php

namespace App\Services;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use App\Features\Refunds\Models\RefundRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Abstracts Paystack/Flutterwave for refunds and payouts (organizer
 * settlements). Retargeted from the original Stripe/PayPal-based spec
 * per project direction - see PaystackService/FlutterwaveService for
 * the underlying per-gateway API calls this wraps.
 */
class PaymentGatewayService
{
    public function __construct(
        private PaystackService $paystack,
        private FlutterwaveService $flutterwave,
    ) {
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

        if (! $order || ! $order->payment_reference) {
            throw new \RuntimeException("Refund request {$refundRequestId}: no payment reference on the associated order.");
        }

        $amount = (float) ($refundRequest->approved_amount ?? $refundRequest->requested_amount);

        try {
            $result = $order->payment_gateway === 'paystack'
                ? $this->paystack->refund($order->payment_reference, $amount)
                : $this->flutterwave->refund($order->payment_reference, $amount);
        } catch (\Throwable $e) {
            Log::error("PaymentGatewayService::processRefund failed for refund request {$refundRequestId}: " . $e->getMessage());
            throw $e;
        }

        $refundId = $this->parseGatewayResponse($order->payment_gateway, $result);

        $refundRequest->update([
            'payment_gateway_refund_id' => $refundId,
            'payment_gateway_response' => $result,
        ]);

        Payment::where('order_id', $order->id)->update(['status' => 'refunded']);

        return $result;
    }

    /**
     * Extracts the gateway's own refund identifier from its response
     * shape, which differs between Paystack and Flutterwave.
     */
    private function parseGatewayResponse(string $gateway, array $response): ?string
    {
        return $gateway === 'paystack'
            ? ($response['id'] ?? null)               // Paystack: numeric refund id
            : ($response['flw_ref'] ?? $response['id'] ?? null); // Flutterwave: flw_ref
    }

    /**
     * Initiates a payout (organizer settlement) to a bank account.
     *
     * @param array $bankDetails Expected keys: account_number, bank_code
     *   (Flutterwave) / account_number, bank_code, account_name
     *   (Paystack, via a transfer recipient). Collecting/storing these
     *   per-organizer is a separate concern this service doesn't own -
     *   see class doc.
     */
    public function initiatePayout(string $gateway, float $amount, array $bankDetails, string $narration = 'Payout'): array
    {
        $reference = 'pay_' . Str::uuid();

        return $gateway === 'paystack'
            ? $this->initiatePaystackPayout($amount, $bankDetails, $narration, $reference)
            : $this->initiateFlutterwavePayout($amount, $bankDetails, $narration, $reference);
    }

    /**
     * Paystack payouts are two steps: create a transfer recipient, then
     * initiate the transfer against that recipient.
     * Docs: https://paystack.com/docs/transfers/
     */
    private function initiatePaystackPayout(float $amount, array $bankDetails, string $narration, string $reference): array
    {
        $secretKey = config('payment.gateways.paystack.secret_key');
        $baseUrl = rtrim(config('payment.gateways.paystack.payment_url', 'https://api.paystack.co'), '/');

        $recipientResponse = Http::withToken($secretKey)->acceptJson()->post("{$baseUrl}/transferrecipient", [
            'type' => 'nuban',
            'name' => $bankDetails['account_name'] ?? 'Organizer',
            'account_number' => $bankDetails['account_number'],
            'bank_code' => $bankDetails['bank_code'],
            'currency' => config('payment.currency', 'NGN'),
        ]);

        if ($recipientResponse->failed()) {
            throw new \RuntimeException('Paystack transfer recipient creation failed: ' . $recipientResponse->body());
        }

        $recipientCode = $recipientResponse->json('data.recipient_code');

        $transferResponse = Http::withToken($secretKey)->acceptJson()->post("{$baseUrl}/transfer", [
            'source' => 'balance',
            'amount' => (int) round($amount * 100), // kobo
            'recipient' => $recipientCode,
            'reason' => $narration,
            'reference' => $reference,
        ]);

        if ($transferResponse->failed()) {
            throw new \RuntimeException('Paystack transfer failed: ' . $transferResponse->body());
        }

        return $transferResponse->json('data');
    }

    /**
     * Flutterwave payouts are a single-step transfer call.
     * Docs: https://developer.flutterwave.com/v3.0/reference/create-a-transfer
     */
    private function initiateFlutterwavePayout(float $amount, array $bankDetails, string $narration, string $reference): array
    {
        $secretKey = config('payment.gateways.flutterwave.secret_key');
        $baseUrl = rtrim(config('payment.gateways.flutterwave.payment_url', 'https://api.flutterwave.com/v3'), '/');

        $response = Http::withToken($secretKey)->acceptJson()->post("{$baseUrl}/transfers", [
            'account_bank' => $bankDetails['bank_code'],
            'account_number' => $bankDetails['account_number'],
            'amount' => $amount,
            'currency' => config('payment.currency', 'NGN'),
            'narration' => $narration,
            'reference' => $reference,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Flutterwave transfer failed: ' . $response->body());
        }

        return $response->json('data');
    }

    /**
     * Checks the status of a previously-initiated payout/transfer.
     */
    public function checkTransferStatus(string $gateway, string $transferReference): array
    {
        $secretKey = config("payment.gateways.{$gateway}.secret_key");
        $baseUrl = rtrim(config("payment.gateways.{$gateway}.payment_url"), '/');

        $url = $gateway === 'paystack'
            ? "{$baseUrl}/transfer/verify/{$transferReference}"
            : "{$baseUrl}/transfers/{$transferReference}";

        $response = Http::withToken($secretKey)->acceptJson()->get($url);

        if ($response->failed()) {
            throw new \RuntimeException("Failed to check {$gateway} transfer status: " . $response->body());
        }

        return $response->json('data');
    }
}

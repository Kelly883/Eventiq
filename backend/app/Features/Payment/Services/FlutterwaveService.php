<?php

namespace App\Features\Payment\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin client around Flutterwave's REST API using Laravel's HTTP client,
 * rather than a third-party wrapper package. See config/payment.php for
 * credentials (FLUTTERWAVE_PUBLIC_KEY / FLUTTERWAVE_SECRET_KEY /
 * FLUTTERWAVE_ENCRYPTION_KEY / FLUTTERWAVE_BASE_URL).
 *
 * Docs: https://developer.flutterwave.com/reference/endpoints/payments
 */
class FlutterwaveService
{
    private string $secretKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('payment.flutterwave.secret_key');
        $this->baseUrl = rtrim((string) config('payment.flutterwave.base_url'), '/');

        if ($this->secretKey === '') {
            Log::warning('FlutterwaveService instantiated without FLUTTERWAVE_SECRET_KEY set.');
        }
    }

    private function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->acceptJson();
    }

    /**
     * Create a payment link (Standard flow). Amount is in the major
     * currency unit (e.g. naira, not kobo).
     *
     * @return array{link: string}
     */
    public function initializePayment(string $email, float $amount, string $txRef, array $meta = []): array
    {
        $response = $this->client()->post('/payments', [
            'tx_ref' => $txRef,
            'amount' => $amount,
            'currency' => config('payment.currency', 'NGN'),
            'redirect_url' => config('payment.flutterwave.callback_url'),
            'customer' => ['email' => $email],
            'meta' => $meta,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Flutterwave initialize failed: '.$response->body());
        }

        return $response->json('data');
    }

    /**
     * Verify a transaction by Flutterwave's numeric transaction id
     * (returned in the webhook/redirect payload as `transaction_id`).
     */
    public function verifyTransaction(string $transactionId): array
    {
        $response = $this->client()->get("/transactions/{$transactionId}/verify");

        if ($response->failed()) {
            throw new RuntimeException('Flutterwave verify failed: '.$response->body());
        }

        return $response->json('data');
    }
}

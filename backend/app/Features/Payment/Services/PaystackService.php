<?php

namespace App\Features\Payment\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin client around Paystack's REST API using Laravel's HTTP client,
 * rather than a third-party wrapper package. See config/payment.php for
 * credentials (PAYSTACK_PUBLIC_KEY / PAYSTACK_SECRET_KEY / PAYSTACK_BASE_URL).
 *
 * Docs: https://paystack.com/docs/api/transaction/
 */
class PaystackService
{
    private string $secretKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('payment.paystack.secret_key');
        $this->baseUrl = rtrim((string) config('payment.paystack.base_url'), '/');

        if ($this->secretKey === '') {
            Log::warning('PaystackService instantiated without PAYSTACK_SECRET_KEY set.');
        }
    }

    private function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->acceptJson();
    }

    /**
     * Initialize a transaction. Amount must be in kobo (smallest NGN unit).
     *
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function initializeTransaction(string $email, int $amountKobo, string $reference, array $metadata = []): array
    {
        $response = $this->client()->post('/transaction/initialize', [
            'email' => $email,
            'amount' => $amountKobo,
            'reference' => $reference,
            'currency' => config('payment.currency', 'NGN'),
            'callback_url' => config('payment.paystack.callback_url'),
            'metadata' => $metadata,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Paystack initialize failed: '.$response->body());
        }

        return $response->json('data');
    }

    /**
     * Verify a transaction by reference.
     */
    public function verifyTransaction(string $reference): array
    {
        $response = $this->client()->get("/transaction/verify/{$reference}");

        if ($response->failed()) {
            throw new RuntimeException('Paystack verify failed: '.$response->body());
        }

        return $response->json('data');
    }
}

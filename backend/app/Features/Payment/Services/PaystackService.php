<?php

namespace App\Features\Payment\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected string $publicKey;
    protected string $secretKey;
    protected string $baseUrl;

    public function __construct(string $publicKey, string $secretKey, string $baseUrl = 'https://api.paystack.co')
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Initialize a payment transaction on Paystack
     */
    public function initializeTransaction(array $data)
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->post("{$this->baseUrl}/transaction/initialize", [
                    'email' => $data['email'],
                    'amount' => $data['amount'] * 100, // Paystack amount is in kobo
                    'reference' => $data['reference'] ?? null,
                    'callback_url' => $data['callback_url'] ?? null,
                    'metadata' => $data['metadata'] ?? [],
                ]);

            if ($response->successful()) {
                return $response->json()['data'];
            }

            Log::error('Paystack Initialization Failed: ' . $response->body());
            throw new \Exception('Failed to initialize Paystack transaction: ' . ($response->json()['message'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            Log::error('Paystack Initialization Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify a Paystack transaction by reference
     */
    public function verifyTransaction(string $reference)
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->get("{$this->baseUrl}/transaction/verify/" . urlencode($reference));

            if ($response->successful()) {
                return $response->json()['data'];
            }

            Log::error('Paystack Verification Failed for ref ' . $reference . ': ' . $response->body());
            throw new \Exception('Failed to verify Paystack transaction: ' . ($response->json()['message'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            Log::error('Paystack Verification Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process a refund
     */
    public function refund(string $transactionId, float $amount = null, string $reason = '')
    {
        try {
            $payload = [
                'transaction' => $transactionId,
                'reason' => $reason,
            ];

            if ($amount !== null) {
                $payload['amount'] = $amount * 100; // in kobo
            }

            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->post("{$this->baseUrl}/refund", $payload);

            if ($response->successful()) {
                return $response->json()['data'];
            }

            Log::error('Paystack Refund Failed: ' . $response->body());
            throw new \Exception('Failed to process Paystack refund: ' . ($response->json()['message'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            Log::error('Paystack Refund Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get customer details from Paystack
     */
    public function getCustomer(string $customerCodeOrEmail)
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->get("{$this->baseUrl}/customer/" . urlencode($customerCodeOrEmail));

            if ($response->successful()) {
                return $response->json()['data'];
            }

            Log::error('Paystack Get Customer Failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Paystack Get Customer Exception: ' . $e->getMessage());
            return null;
        }
    }
}

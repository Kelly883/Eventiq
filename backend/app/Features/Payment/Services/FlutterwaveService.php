<?php

namespace App\Features\Payment\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveService
{
    protected string $publicKey;
    protected string $secretKey;
    protected string $encryptionKey;
    protected string $baseUrl;

    public function __construct(string $publicKey, string $secretKey, string $encryptionKey = '', string $baseUrl = 'https://api.flutterwave.com/v3')
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
        $this->encryptionKey = $encryptionKey;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Initialize a Flutterwave payment link / transaction
     */
    public function initializeTransaction(array $data)
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->post("{$this->baseUrl}/payments", [
                    'tx_ref' => $data['reference'] ?? uniqid('flw_'),
                    'amount' => $data['amount'],
                    'currency' => $data['currency'] ?? 'NGN',
                    'redirect_url' => $data['callback_url'] ?? null,
                    'customer' => [
                        'email' => $data['email'],
                        'name' => $data['name'] ?? 'Customer',
                        'phonenumber' => $data['phone'] ?? null,
                    ],
                    'customizations' => $data['customizations'] ?? [
                        'title' => config('app.name', 'EventIQ'),
                        'description' => 'Event Ticketing Payment',
                    ],
                    'meta' => $data['metadata'] ?? [],
                ]);

            if ($response->successful()) {
                return $response->json()['data'];
            }

            Log::error('Flutterwave Initialization Failed: ' . $response->body());
            throw new \Exception('Failed to initialize Flutterwave payment: ' . ($response->json()['message'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            Log::error('Flutterwave Initialization Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify a Flutterwave transaction by ID
     */
    public function verifyTransaction(string $transactionId)
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->get("{$this->baseUrl}/transactions/" . urlencode($transactionId) . "/verify");

            if ($response->successful()) {
                return $response->json()['data'];
            }

            Log::error('Flutterwave Verification Failed for ID ' . $transactionId . ': ' . $response->body());
            throw new \Exception('Failed to verify Flutterwave transaction: ' . ($response->json()['message'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            Log::error('Flutterwave Verification Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process a Flutterwave refund
     */
    public function refund(string $transactionId, float $amount = null, string $reason = '')
    {
        try {
            $payload = [];
            if ($amount !== null) {
                $payload['amount'] = $amount;
            }

            $response = Http::withToken($this->secretKey)
                ->acceptJson()
                ->post("{$this->baseUrl}/transactions/" . urlencode($transactionId) . "/refund", $payload);

            if ($response->successful()) {
                return $response->json()['data'];
            }

            Log::error('Flutterwave Refund Failed: ' . $response->body());
            throw new \Exception('Failed to process Flutterwave refund: ' . ($response->json()['message'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            Log::error('Flutterwave Refund Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify Webhook Hash / Signature
     */
    public function verifyWebhookSignature(string $signatureHeader, string $secretHash)
    {
        return $signatureHeader === $secretHash;
    }
}

<?php

namespace App\Features\Payment\Services;

use App\Features\Payment\Contracts\PaymentGatewayContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveService implements PaymentGatewayContract
{
    protected string $publicKey;
    protected string $secretKey;
    protected string $encryptionKey;
    protected string $baseUrl;
    protected string $webhookSecretHash;

    public function __construct(string $publicKey, string $secretKey, string $encryptionKey = '', string $baseUrl = 'https://api.flutterwave.com/v3', string $webhookSecretHash = '')
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
        $this->encryptionKey = $encryptionKey;
        $this->baseUrl = $baseUrl;
        // Flutterwave lets you configure a dedicated "Secret Hash" in the
        // dashboard specifically for webhook verification, separate from
        // the API secret key. Falls back to secretKey only if no dedicated
        // hash is configured, so existing setups don't silently break.
        $this->webhookSecretHash = $webhookSecretHash !== '' ? $webhookSecretHash : $secretKey;
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
     * Initiate a bank transfer (organizer payout) - a single-step call,
     * unlike Paystack's recipient-then-transfer flow.
     * Docs: https://developer.flutterwave.com/v3.0/reference/create-a-transfer
     */
    public function transfer(float $amount, array $bankDetails, string $narration, string $reference): array
    {
        $response = Http::withToken($this->secretKey)->acceptJson()->post("{$this->baseUrl}/transfers", [
            'account_bank' => $bankDetails['bank_code'],
            'account_number' => $bankDetails['account_number'],
            'amount' => $amount,
            'currency' => config('payment.currency', 'NGN'),
            'narration' => $narration,
            'reference' => $reference,
        ]);

        if ($response->failed()) {
            Log::error('Flutterwave transfer failed: ' . $response->body());
            throw new \Exception('Flutterwave transfer failed: ' . $response->body());
        }

        return $response->json('data');
    }

    public function checkTransferStatus(string $transferReference): array
    {
        $response = Http::withToken($this->secretKey)->acceptJson()->get("{$this->baseUrl}/transfers/{$transferReference}");

        if ($response->failed()) {
            throw new \Exception('Failed to check Flutterwave transfer status: ' . $response->body());
        }

        return $response->json('data');
    }

    /**
     * Verify a Flutterwave webhook's `verif-hash` header against the
     * configured secret hash. Flutterwave uses a plain shared-secret
     * comparison here (not HMAC), per their webhook docs.
     */
    public function verifyWebhookSignature(string $signatureHeader): bool
    {
        if ($signatureHeader === '' || $this->webhookSecretHash === '') {
            return false;
        }

        return hash_equals($this->webhookSecretHash, $signatureHeader);
    }
}

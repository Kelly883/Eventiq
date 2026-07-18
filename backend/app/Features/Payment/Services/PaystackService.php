<?php

namespace App\Features\Payment\Services;

use App\Features\Payment\Contracts\PaymentGatewayContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService implements PaymentGatewayContract
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
     * Initiate a bank transfer (organizer payout). Paystack requires two
     * steps: create a transfer recipient, then initiate the transfer
     * against that recipient. Docs: https://paystack.com/docs/transfers/
     */
    public function transfer(float $amount, array $bankDetails, string $narration, string $reference): array
    {
        $recipientResponse = Http::withToken($this->secretKey)->acceptJson()->post("{$this->baseUrl}/transferrecipient", [
            'type' => 'nuban',
            'name' => $bankDetails['account_name'] ?? 'Organizer',
            'account_number' => $bankDetails['account_number'],
            'bank_code' => $bankDetails['bank_code'],
            'currency' => config('payment.currency', 'NGN'),
        ]);

        if ($recipientResponse->failed()) {
            Log::error('Paystack transfer recipient creation failed: ' . $recipientResponse->body());
            throw new \Exception('Paystack transfer recipient creation failed: ' . $recipientResponse->body());
        }

        $recipientCode = $recipientResponse->json('data.recipient_code');

        $transferResponse = Http::withToken($this->secretKey)->acceptJson()->post("{$this->baseUrl}/transfer", [
            'source' => 'balance',
            'amount' => (int) round($amount * 100), // kobo
            'recipient' => $recipientCode,
            'reason' => $narration,
            'reference' => $reference,
        ]);

        if ($transferResponse->failed()) {
            Log::error('Paystack transfer failed: ' . $transferResponse->body());
            throw new \Exception('Paystack transfer failed: ' . $transferResponse->body());
        }

        return $transferResponse->json('data');
    }

    public function checkTransferStatus(string $transferReference): array
    {
        $response = Http::withToken($this->secretKey)->acceptJson()->get("{$this->baseUrl}/transfer/verify/{$transferReference}");

        if ($response->failed()) {
            throw new \Exception('Failed to check Paystack transfer status: ' . $response->body());
        }

        return $response->json('data');
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

    /**
     * Verify a Paystack webhook's signature.
     *
     * Paystack signs the raw request body with HMAC-SHA512 using your
     * secret key, sent in the `x-paystack-signature` header. Must be
     * computed against the raw (unparsed) body, not a re-encoded JSON
     * string, since key order / whitespace differences would break it.
     *
     * Docs: https://paystack.com/docs/payments/webhooks/#verify-event-origin
     */
    public function verifyWebhookSignature(string $rawPayload, string $signatureHeader): bool
    {
        if ($signatureHeader === '' || $this->secretKey === '') {
            return false;
        }

        $expected = hash_hmac('sha512', $rawPayload, $this->secretKey);

        return hash_equals($expected, $signatureHeader);
    }
}

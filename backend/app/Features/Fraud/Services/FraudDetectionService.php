<?php

namespace App\Features\Fraud\Services;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Ticket;
use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

/**
 * Single entry point for fraud-related operations: Sift Science scoring,
 * Paystack/Flutterwave transaction verification, and internal rule-based checks
 * (velocity, duplicate tickets, card testing, device fingerprinting, IP reputation,
 * and max ticket limits).
 */
class FraudDetectionService
{
    public function __construct(
        private PaystackService $paystack,
        private FlutterwaveService $flutterwave,
    ) {
    }

    /**
     * Orchestrates a full fraud risk assessment for a transaction.
     *
     * @param array $transaction Expected keys: user_id, email, amount,
     *   reference (gateway transaction ref), provider ('paystack'|'flutterwave'),
     *   ip, session_id, device_id (optional), ticket_tier_id (optional), qr_code (optional), ticket_count (optional)
     */
    public function detectFraudRisk(array $transaction): array
    {
        // 1. Fetch transaction metadata from provider to get card BIN, Last4, and brand details securely
        $paymentDetails = [];
        $cardFingerprint = null;
        $reference = $transaction['reference'] ?? null;
        $provider = $transaction['provider'] ?? null;

        if ($reference && $provider) {
            try {
                $details = $this->getTransactionDetails($reference, $provider);
                if ($provider === 'paystack') {
                    $auth = $details['data']['authorization'] ?? [];
                    $paymentDetails = [
                        'bin' => $auth['bin'] ?? null,
                        'last4' => $auth['last4'] ?? null,
                        'brand' => $auth['brand'] ?? null,
                        'country' => $auth['country_code'] ?? null,
                    ];
                    $cardFingerprint = $auth['signature'] ?? null;
                } elseif ($provider === 'flutterwave') {
                    $card = $details['data']['card'] ?? [];
                    $paymentDetails = [
                        'bin' => $card['first_6digits'] ?? null,
                        'last4' => $card['last_4digits'] ?? null,
                        'brand' => $card['brand'] ?? null,
                        'country' => $card['country'] ?? null,
                    ];
                    $cardFingerprint = $card['token'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning('FraudDetectionService: Could not retrieve provider metadata for enrichment: ' . $e->getMessage());
            }
        }

        // Add any card fingerprint to transaction array for down-stream checks
        if ($cardFingerprint) {
            $transaction['card_fingerprint'] = $cardFingerprint;
        }

        // 2. Perform assessments
        $siftScore = $this->reportToSift($transaction, $paymentDetails);
        $velocity = $this->checkVelocity($transaction['user_id'] ?? null, $transaction['amount'] ?? 0);
        $cardTesting = $this->detectCardTesting($transaction['user_id'] ?? null, $transaction['ip'] ?? null);
        $deviceCheck = $this->checkDeviceFingerprint($transaction['device_id'] ?? null);
        $ipCheck = $this->checkIpReputation($transaction['ip'] ?? null);

        // Check tickets per transaction limit
        $ticketCount = $transaction['ticket_count'] ?? 1;
        $maxTicketsLimit = config('fraud.thresholds.max_tickets_per_transaction', 10);
        $ticketLimitExceeded = $ticketCount > $maxTicketsLimit;

        // Check duplicate ticket if qr code is supplied
        $duplicateTicket = false;
        if (!empty($transaction['ticket_tier_id']) && !empty($transaction['qr_code'])) {
            $dupCheck = $this->detectDuplicateTickets((int)$transaction['ticket_tier_id'], $transaction['qr_code']);
            $duplicateTicket = $dupCheck['duplicate'] ?? false;
        }

        $riskScore = $siftScore['score'] ?? 0;
        $thresholds = config('fraud.thresholds');

        $flags = [];
        if ($velocity['exceeded'] ?? false) {
            $flags[] = 'velocity_exceeded';
        }
        if ($cardTesting['suspected'] ?? false) {
            $flags[] = 'card_testing_suspected';
        }
        if ($deviceCheck['suspected'] ?? false) {
            $flags[] = 'device_limit_exceeded';
        }
        if ($ipCheck['suspected'] ?? false) {
            $flags[] = 'ip_limit_exceeded';
        }
        if ($ticketLimitExceeded) {
            $flags[] = 'max_tickets_limit_exceeded';
        }
        if ($duplicateTicket) {
            $flags[] = 'duplicate_ticket_detected';
        }

        $decision = match (true) {
            $riskScore >= ($thresholds['high_risk'] ?? 75) || ! empty($flags) => 'block',
            $riskScore >= ($thresholds['medium_risk'] ?? 31) => 'review',
            default => 'approve',
        };

        $result = [
            'decision' => $decision,
            'risk_score' => $riskScore,
            'flags' => $flags,
            'sift' => $siftScore,
            'velocity' => $velocity,
            'card_testing' => $cardTesting,
            'device_check' => $deviceCheck,
            'ip_check' => $ipCheck,
            'ticket_limit' => [
                'count' => $ticketCount,
                'limit' => $maxTicketsLimit,
                'exceeded' => $ticketLimitExceeded,
            ],
            'duplicate_ticket' => $duplicateTicket,
        ];

        // Increment device/IP counters if approved or reviewing
        if ($decision !== 'block') {
            if (!empty($transaction['device_id'])) {
                $deviceKey = "device_tx_count_" . md5($transaction['device_id']);
                Cache::put($deviceKey, ((int)Cache::get($deviceKey, 0)) + 1, now()->addDay());
            }
            if (!empty($transaction['ip'])) {
                $ipKey = "ip_tx_count_" . md5($transaction['ip']);
                Cache::put($ipKey, ((int)Cache::get($ipKey, 0)) + 1, now()->addDay());
            }
        }

        $this->logFraudEvent(array_merge($transaction, ['result' => $result]));

        return $result;
    }

    /**
     * Validates a webhook payload signature using the appropriate provider.
     */
    public function validateWebhook(string $provider, string $payload, string $signature): bool
    {
        return match (strtolower($provider)) {
            'paystack' => $this->paystack->verifyWebhookSignature($payload, $signature),
            'flutterwave' => $this->flutterwave->verifyWebhookSignature($signature),
            default => false,
        };
    }

    /**
     * Checks if a device ID has exceeded the maximum transaction limit or is flagged.
     */
    public function checkDeviceFingerprint(?string $deviceId): array
    {
        if (! $deviceId) {
            return ['suspected' => false, 'reason' => 'No device ID provided', 'checked' => false];
        }

        $limit = config('fraud.thresholds.max_transactions_per_device', 5);
        $cacheKey = "device_tx_count_" . md5($deviceId);
        $count = (int) Cache::get($cacheKey, 0);

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'device_id')) {
            $dbCount = Order::where('device_id', $deviceId)
                ->where('created_at', '>=', now()->subDay())
                ->count();
            $count = max($count, $dbCount);
        }

        return [
            'suspected' => $count >= $limit,
            'count' => $count,
            'limit' => $limit,
            'checked' => true,
        ];
    }

    /**
     * Checks if an IP address is suspicious or has exceeded transaction limits.
     */
    public function checkIpReputation(?string $ipAddress): array
    {
        if (! $ipAddress) {
            return ['suspected' => false, 'reason' => 'No IP address provided', 'checked' => false];
        }

        if (in_array($ipAddress, ['127.0.0.1', '::1'])) {
            return ['suspected' => false, 'reason' => 'Localhost IP', 'checked' => true];
        }

        $limit = config('fraud.thresholds.max_transactions_per_ip', 10);
        $cacheKey = "ip_tx_count_" . md5($ipAddress);
        $count = (int) Cache::get($cacheKey, 0);

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'ip_address')) {
            $dbCount = Order::where('ip_address', $ipAddress)
                ->where('created_at', '>=', now()->subDay())
                ->count();
            $count = max($count, $dbCount);
        }

        return [
            'suspected' => $count >= $limit,
            'count' => $count,
            'limit' => $limit,
            'checked' => true,
        ];
    }

    /**
     * Verify a Paystack transaction actually succeeded.
     */
    public function verifyPaystackTransaction(string $reference): array
    {
        return $this->paystack->verifyTransaction($reference);
    }

    /**
     * Verify a Flutterwave transaction actually succeeded.
     */
    public function verifyFlutterwaveTransaction(string $transactionId): array
    {
        return $this->flutterwave->verifyTransaction($transactionId);
    }

    /**
     * Resolves transaction details depending on the payment provider.
     */
    public function getTransactionDetails(string $reference, ?string $provider = null): array
    {
        if ($provider) {
            return match (strtolower($provider)) {
                'paystack' => $this->verifyPaystackTransaction($reference),
                'flutterwave' => $this->verifyFlutterwaveTransaction($reference),
                default => throw new \InvalidArgumentException("Unknown payment provider: {$provider}"),
            };
        }

        // Heuristics: if starts with flw, FLW, or is numeric/long ID, try Flutterwave, else default to Paystack
        if (str_starts_with($reference, 'flw') || str_starts_with($reference, 'FLW')) {
            return $this->verifyFlutterwaveTransaction($reference);
        }

        try {
            return $this->verifyPaystackTransaction($reference);
        } catch (\Throwable $e) {
            try {
                return $this->verifyFlutterwaveTransaction($reference);
            } catch (\Throwable $ex) {
                throw new \RuntimeException("Could not resolve transaction details from either provider for ref: {$reference}");
            }
        }
    }

    /**
     * Flags a user exceeding the configured order-velocity thresholds.
     */
    public function checkVelocity(?int $userId, float $amount): array
    {
        if (! $userId || ! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'user_id')) {
            Log::warning('FraudDetectionService::checkVelocity skipped - orders table/columns not available yet.');

            return ['exceeded' => false, 'count_1h' => null, 'count_24h' => null, 'checked' => false];
        }

        $thresholds = config('fraud.thresholds');

        $count1h = Order::where('user_id', $userId)->where('created_at', '>=', now()->subHour())->count();
        $count24h = Order::where('user_id', $userId)->where('created_at', '>=', now()->subDay())->count();

        return [
            'exceeded' => $count1h > ($thresholds['velocity_limit_1h'] ?? 3) || $count24h > ($thresholds['velocity_limit_24h'] ?? 10),
            'count_1h' => $count1h,
            'count_24h' => $count24h,
            'checked' => true,
        ];
    }

    /**
     * Flags a QR code being issued to more than one ticket for the same tier.
     */
    public function detectDuplicateTickets(int $ticketTierId, string $qrCode): array
    {
        if (! Schema::hasTable('tickets') || ! Schema::hasColumn('tickets', 'qr_code')) {
            Log::warning('FraudDetectionService::detectDuplicateTickets skipped - tickets.qr_code column not available yet.');

            return ['duplicate' => false, 'matches' => null, 'checked' => false];
        }

        $matches = Ticket::where('ticket_tier_id', $ticketTierId)
            ->where('qr_code', $qrCode)
            ->count();

        return ['duplicate' => $matches > 1, 'matches' => $matches, 'checked' => true];
    }

    /**
     * Flags a user with an unusually high number of distinct failed/attempted
     * transactions in a short window - a classic card-testing pattern.
     */
    public function detectCardTesting(?int $userId, ?string $ip = null): array
    {
        $threshold = config('fraud.thresholds.card_testing_threshold', 5);

        // Check if there is a payment attempts table
        if (Schema::hasTable('payment_attempts')) {
            $query = \DB::table('payment_attempts')
                ->where('created_at', '>=', now()->subHour());

            if ($userId) {
                $query->where('user_id', $userId);
            } elseif ($ip) {
                $query->where('ip_address', $ip);
            } else {
                return ['suspected' => false, 'checked' => false];
            }

            $attempts = $query->count();
            return [
                'suspected' => $attempts >= $threshold,
                'count_1h' => $attempts,
                'checked' => true,
            ];
        }

        // Fallback to checking orders table for pending/failed checkouts
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'status')) {
            $query = Order::where('created_at', '>=', now()->subHour())
                ->whereIn('status', ['failed', 'pending']);

            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                return ['suspected' => false, 'checked' => false];
            }

            $attempts = $query->count();
            return [
                'suspected' => $attempts >= $threshold,
                'count_1h' => $attempts,
                'checked' => true,
            ];
        }

        return ['suspected' => false, 'checked' => false];
    }

    /**
     * Logs the final evaluation event.
     */
    public function logFraudEvent(array $event): void
    {
        Log::info('Fraud event evaluated', [
            'user_id' => $event['user_id'] ?? null,
            'reference' => $event['reference'] ?? null,
            'provider' => $event['provider'] ?? null,
            'decision' => $event['result']['decision'] ?? null,
            'risk_score' => $event['result']['risk_score'] ?? null,
            'flags' => $event['result']['flags'] ?? [],
        ]);
    }

    /**
     * Sends a transaction event to Sift's Events API and fetches the risk score.
     */
    public function reportToSift(array $transaction, array $paymentDetails = []): array
    {
        $apiKey = config('fraud.sift.api_key');
        $accountId = config('fraud.sift.account_id');

        if (! $apiKey) {
            Log::warning('FraudDetectionService::reportToSift skipped - SIFT_API_KEY not configured.');
            return ['score' => 0, 'reported' => false];
        }

        $baseUrl = rtrim(config('fraud.sift.api_base_url'), '/');

        try {
            $properties = [
                '$user_id' => (string) ($transaction['user_id'] ?? 'guest'),
                '$user_email' => $transaction['email'] ?? null,
                '$amount' => isset($transaction['amount']) ? (int) round($transaction['amount'] * 1_000_000) : null,
                '$currency_code' => $transaction['currency'] ?? config('payment.currency', 'NGN'),
                '$transaction_type' => '$sale',
                '$transaction_status' => '$success',
                '$ip' => $transaction['ip'] ?? null,
                '$session_id' => $transaction['session_id'] ?? null,
            ];

            if (! empty($paymentDetails)) {
                $paymentMethod = [
                    '$payment_type' => '$card',
                ];
                if (! empty($paymentDetails['bin'])) {
                    $paymentMethod['$card_bin'] = $paymentDetails['bin'];
                }
                if (! empty($paymentDetails['last4'])) {
                    $paymentMethod['$card_last4'] = $paymentDetails['last4'];
                }
                if (! empty($paymentDetails['brand'])) {
                    $paymentMethod['$card_brand'] = strtolower($paymentDetails['brand']);
                }
                if (! empty($paymentDetails['country'])) {
                    $paymentMethod['$card_country'] = strtoupper($paymentDetails['country']);
                }
                $properties['$payment_methods'] = [$paymentMethod];
            }

            // 1. Send the transaction event
            if (class_exists('SiftClient')) {
                $client = new \SiftClient([
                    'api_key' => $apiKey,
                    'account_id' => $accountId,
                ]);
                $response = $client->track('$transaction', $properties);
                if (! $response->isOk()) {
                    Log::error('Sift SDK track event failed: ' . $response->message);
                }
            } else {
                $eventResponse = Http::asJson()->post("{$baseUrl}/events", array_merge([
                    '$type' => '$transaction',
                    '$api_key' => $apiKey,
                ], $properties));

                if ($eventResponse->failed()) {
                    Log::error('Sift HTTP event submission failed: ' . $eventResponse->body());
                    return ['score' => 0, 'reported' => false];
                }
            }

            // 2. Retrieve user score
            $userId = (string) ($transaction['user_id'] ?? 'guest');
            if (class_exists('SiftClient') && $accountId) {
                // Use official SDK if loaded
                $client = new \SiftClient([
                    'api_key' => $apiKey,
                    'account_id' => $accountId,
                ]);
                $scoreResponse = $client->score($userId, ['abuse_types' => ['payment_abuse']]);
                if ($scoreResponse->isOk()) {
                    $score = ($scoreResponse->body['scores']['payment_abuse']['score'] ?? 0) * 100;
                    return ['score' => (int) round($score), 'reported' => true];
                }
            }

            // Fallback HTTP score fetch
            if ($accountId) {
                $scoreResponse = Http::get("{$baseUrl}/score/{$userId}", [
                    'api_key' => $apiKey,
                    'account_id' => $accountId,
                    'abuse_types' => 'payment_abuse',
                ]);

                if ($scoreResponse->successful()) {
                    $score = $scoreResponse->json('scores.payment_abuse.score', 0) * 100;
                    return ['score' => (int) round($score), 'reported' => true];
                }
            }

            return ['score' => 0, 'reported' => true];
        } catch (\Throwable $e) {
            Log::error('Sift integration exception: ' . $e->getMessage());
            return ['score' => 0, 'reported' => false, 'error' => true];
        }
    }
}

<?php

namespace App\Features\Fraud\Services;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Ticket;
use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Single entry point for fraud-related operations: Sift Science scoring,
 * Paystack/Flutterwave transaction verification, and internal rule-based
 * checks (velocity, duplicate tickets, card testing).
 *
 * NOTE: velocity/duplicate-ticket checks assume Order has user_id/amount/
 * created_at columns and Ticket has ticket_tier_id/qr_code columns. Both
 * models are currently empty stubs with no migration defining those
 * columns yet in this repo, so these methods guard with Schema::hasColumn
 * checks and log + fail safe (don't block) rather than crash or silently
 * assume schema that may not exist.
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
     *   ip, session_id, ticket_tier_id (optional), qr_code (optional)
     */
    public function detectFraudRisk(array $transaction): array
    {
        $siftScore = $this->reportToSift($transaction);
        $velocity = $this->checkVelocity($transaction['user_id'] ?? null, $transaction['amount'] ?? 0);
        $cardTesting = $this->detectCardTesting($transaction['user_id'] ?? null);

        $riskScore = $siftScore['score'] ?? 0;
        $thresholds = config('fraud.thresholds');

        $flags = array_filter([
            $velocity['exceeded'] ?? false ? 'velocity_exceeded' : null,
            $cardTesting['suspected'] ?? false ? 'card_testing_suspected' : null,
        ]);

        $decision = match (true) {
            $riskScore >= $thresholds['high_risk'] || ! empty($flags) => 'block',
            $riskScore >= $thresholds['medium_risk'] => 'review',
            default => 'approve',
        };

        $result = [
            'decision' => $decision,
            'risk_score' => $riskScore,
            'flags' => array_values($flags),
            'sift' => $siftScore,
            'velocity' => $velocity,
            'card_testing' => $cardTesting,
        ];

        $this->logFraudEvent(array_merge($transaction, ['result' => $result]));

        return $result;
    }

    /**
     * Verify a Paystack transaction actually succeeded, delegating to the
     * existing PaystackService rather than duplicating HTTP logic.
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

    public function getTransactionDetails(string $reference, string $provider): array
    {
        return match ($provider) {
            'paystack' => $this->verifyPaystackTransaction($reference),
            'flutterwave' => $this->verifyFlutterwaveTransaction($reference),
            default => throw new \InvalidArgumentException("Unknown payment provider: {$provider}"),
        };
    }

    /**
     * Flags a user exceeding the configured order-velocity thresholds
     * (too many orders in a short window - a common fraud/scalping signal).
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
            'exceeded' => $count1h > $thresholds['velocity_limit_1h'] || $count24h > $thresholds['velocity_limit_24h'],
            'count_1h' => $count1h,
            'count_24h' => $count24h,
            'checked' => true,
        ];
    }

    /**
     * Flags a QR code being issued to more than one ticket for the same
     * tier - i.e. a duplicate/cloned ticket.
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
     * Placeholder pending a payment_attempts log table; currently always
     * reports "not checked" rather than fabricating a result.
     */
    public function detectCardTesting(?int $userId): array
    {
        // TODO: implement once payment attempts (including failures) are
        // logged somewhere queryable - Order alone only reflects successful
        // checkouts, not declined attempts, which is what this needs.
        return ['suspected' => false, 'checked' => false];
    }

    public function logFraudEvent(array $event): void
    {
        // Deliberately excludes card/payment secrets - only IDs and the
        // computed decision get logged.
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
     * Sends a $transaction event to Sift's Events API and returns the
     * resulting fraud score for the user.
     *
     * Docs: https://developers.sift.com/docs/curl/events-api/reserved-events/transaction
     */
    public function reportToSift(array $transaction): array
    {
        $apiKey = config('fraud.sift.api_key');

        if (! $apiKey) {
            Log::warning('FraudDetectionService::reportToSift skipped - SIFT_API_KEY not configured.');

            return ['score' => 0, 'reported' => false];
        }

        $baseUrl = rtrim(config('fraud.sift.api_base_url'), '/');

        try {
            $eventResponse = Http::asJson()->post("{$baseUrl}/events", [
                '$type' => '$transaction',
                '$api_key' => $apiKey,
                '$user_id' => (string) ($transaction['user_id'] ?? 'guest'),
                '$user_email' => $transaction['email'] ?? null,
                '$amount' => isset($transaction['amount']) ? (int) round($transaction['amount'] * 1_000_000) : null, // Sift wants micros
                '$currency_code' => $transaction['currency'] ?? config('payment.currency', 'NGN'),
                '$transaction_type' => '$sale',
                '$transaction_status' => '$success',
                '$ip' => $transaction['ip'] ?? null,
                '$session_id' => $transaction['session_id'] ?? null,
            ]);

            if ($eventResponse->failed()) {
                Log::error('Sift event submission failed: ' . $eventResponse->body());

                return ['score' => 0, 'reported' => false];
            }

            $accountId = config('fraud.sift.account_id');
            $userId = (string) ($transaction['user_id'] ?? 'guest');

            $scoreResponse = Http::get("{$baseUrl}/score/{$userId}", [
                'api_key' => $apiKey,
                'account_id' => $accountId,
                'abuse_types' => 'payment_abuse',
            ]);

            if ($scoreResponse->failed()) {
                Log::error('Sift score fetch failed: ' . $scoreResponse->body());

                return ['score' => 0, 'reported' => true];
            }

            $score = $scoreResponse->json('scores.payment_abuse.score', 0) * 100;

            return ['score' => (int) round($score), 'reported' => true];
        } catch (\Throwable $e) {
            // Fail open on Sift outages rather than blocking checkout entirely -
            // logged for investigation, but doesn't take down payments.
            Log::error('Sift integration exception: ' . $e->getMessage());

            return ['score' => 0, 'reported' => false, 'error' => true];
        }
    }
}

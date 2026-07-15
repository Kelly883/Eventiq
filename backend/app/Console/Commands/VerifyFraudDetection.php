<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Features\Fraud\Services\FraudDetectionService;
use App\Features\Payment\Services\PaystackService;
use App\Features\Payment\Services\FlutterwaveService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class VerifyFraudDetection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fraud:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify and test the Sift, Paystack, and Flutterwave fraud detection system under normal and failure states';

    /**
     * Execute the console command.
     */
    public function handle(FraudDetectionService $fraudService)
    {
        $this->info("====================================================================");
        $this->info("   EVENTIQ FRAUD DETECTION INFRASTRUCTURE VERIFICATION SYSTEM      ");
        $this->info("====================================================================");

        $this->info("\n--- 1. Verification of Service Instantiation ---");
        if ($fraudService instanceof FraudDetectionService) {
            $this->info("✔ FraudDetectionService resolved and instantiated successfully!");
        } else {
            $this->error("❌ Failed to resolve FraudDetectionService.");
            return 1;
        }

        $this->info("\n--- 2. Live Config & Key Expose Verification ---");
        $this->verifyConfig();

        $this->info("\n--- 3. Testing: detectFraudRisk() under Safe (Approve) scenario ---");
        $mockSafeTx = [
            'user_id' => 42,
            'email' => 'good_user@example.com',
            'amount' => 1500,
            'currency' => 'NGN',
            'reference' => 'test_ref_safe_123',
            'provider' => 'paystack',
            'ip' => '192.168.1.50',
            'session_id' => 'sess_safe_999',
            'device_id' => 'dev_abc123',
            'ticket_tier_id' => 1,
            'qr_code' => 'QR_SECURE_GOOD_001',
            'ticket_count' => 2
        ];
        $this->runDetectRiskTest($fraudService, $mockSafeTx, "Safe Customer Transaction");

        $this->info("\n--- 4. Testing: detectFraudRisk() under High-Risk/Suspicious scenarios ---");
        
        // Scenario A: High velocity suspect
        $mockHighRiskTx = [
            'user_id' => 999, // Suspected high-velocity user
            'email' => 'suspect@example.com',
            'amount' => 500000,
            'currency' => 'NGN',
            'reference' => 'test_ref_suspect_456',
            'provider' => 'flutterwave',
            'ip' => '198.51.100.12',
            'session_id' => 'sess_suspect_000',
            'device_id' => 'dev_bad999',
            'ticket_tier_id' => 2,
            'qr_code' => 'QR_BAD_ATTEMPT_111',
            'ticket_count' => 15 // Exceeds config('fraud.thresholds.max_tickets_per_transaction')
        ];
        $this->runDetectRiskTest($fraudService, $mockHighRiskTx, "High-Risk Card/Ticket limit-exceeded Transaction");

        $this->info("\n--- 5. Verifying Component Fraud Rules Individually ---");
        
        // A. checkVelocity
        $this->info("\n[A] Velocity Check:");
        $velocityResult = $fraudService->checkVelocity(42, 1500);
        $this->line("Velocity Exceeded: " . ($velocityResult['exceeded'] ? 'Yes' : 'No'));
        $this->line("Details: Checked: " . ($velocityResult['checked'] ? 'Yes' : 'No') . ", 1h: " . ($velocityResult['count_1h'] ?? 'N/A') . ", 24h: " . ($velocityResult['count_24h'] ?? 'N/A'));

        // B. detectDuplicateTickets
        $this->info("\n[B] Duplicate Ticket Check:");
        $dupResult = $fraudService->detectDuplicateTickets(1, 'QR_TICKET_123');
        $this->line("Duplicate Detected: " . ($dupResult['duplicate'] ? 'Yes' : 'No'));
        $this->line("Matches Count: " . ($dupResult['matches'] ?? 'N/A'));

        // C. checkDeviceFingerprint
        $this->info("\n[C] Device Fingerprint Check:");
        $deviceResult = $fraudService->checkDeviceFingerprint('dev_some_unique_id');
        $this->line("Device Flagged: " . ($deviceResult['suspected'] ? 'Yes' : 'No'));
        $this->line("Device Limit details: " . ($deviceResult['count'] ?? 0) . " / " . ($deviceResult['limit'] ?? 5));

        // D. checkIpReputation
        $this->info("\n[D] IP Reputation Check:");
        $ipResult = $fraudService->checkIpReputation('192.168.1.10');
        $this->line("IP Address Flagged: " . ($ipResult['suspected'] ? 'Yes' : 'No'));
        $this->line("IP Limit details: " . ($ipResult['count'] ?? 0) . " / " . ($ipResult['limit'] ?? 10));

        // E. validateWebhook
        $this->info("\n[E] Webhook Signature Validation Check:");
        $webhookValidPaystack = $fraudService->validateWebhook('paystack', '{"event":"charge.success"}', 'dummy_sig');
        $webhookValidFlutter = $fraudService->validateWebhook('flutterwave', '{"event":"charge.completed"}', 'dummy_sig');
        $this->line("Paystack Webhook Validated (Dummy signature): " . ($webhookValidPaystack ? 'Valid' : 'Invalid/Unverified (Expected)'));
        $this->line("Flutterwave Webhook Validated (Dummy signature): " . ($webhookValidFlutter ? 'Valid' : 'Invalid/Unverified (Expected)'));

        $this->info("\n--- 6. Verify Payment Provider Transactions (With fallback / Graceful network exception handling) ---");
        $this->verifyProviderAPIs($fraudService);

        $this->info("\n--- 7. Failure and Graceful Degrade Testing ---");
        $this->runFailureTesting($fraudService);

        $this->info("\n====================================================================");
        $this->info("             VERIFICATION TESTS FINISHED SUCCESSFULLY!             ");
        $this->info("====================================================================");

        return 0;
    }

    private function verifyConfig()
    {
        $this->line("Sift Account ID: " . (config('fraud.sift.account_id') ?: 'Not Set (Optional)'));
        $this->line("Sift Base URL: " . config('fraud.sift.api_base_url'));
        $this->line("Paystack Secret Configured: " . (config('fraud.paystack.secret_key') ? 'Yes (Value hidden)' : 'No'));
        $this->line("Flutterwave Secret Configured: " . (config('fraud.flutterwave.secret_key') ? 'Yes (Value hidden)' : 'No'));
        
        $this->line("--- Configured Risk Thresholds ---");
        $this->line("High Risk Threshold: " . config('fraud.thresholds.high_risk', 75));
        $this->line("Medium Risk Threshold: " . config('fraud.thresholds.medium_risk', 31));
        $this->line("Velocity Limits (24h / 1h): " . config('fraud.thresholds.velocity_limit_24h', 10) . " / " . config('fraud.thresholds.velocity_limit_1h', 3));
        $this->line("Card Testing Threshold: " . config('fraud.thresholds.card_testing_threshold', 5));
        $this->line("Max Tickets per Transaction: " . config('fraud.thresholds.max_tickets_per_transaction', 10));
        $this->line("Max Transactions per Device: " . config('fraud.thresholds.max_transactions_per_device', 5));
        $this->line("Max Transactions per IP: " . config('fraud.thresholds.max_transactions_per_ip', 10));

        // Confirm that no secrets are exposed in configuration strings
        $secrets = ['SIFT_API_KEY', 'PAYSTACK_SECRET_KEY', 'FLUTTERWAVE_SECRET_KEY'];
        foreach ($secrets as $secret) {
            if (str_contains(json_encode(config('fraud')), env($secret))) {
                if (!empty(env($secret))) {
                    $this->warn("⚠ WARNING: Raw secret '{$secret}' is outputted directly or present in config files!");
                }
            }
        }
    }

    private function runDetectRiskTest(FraudDetectionService $service, array $tx, string $label)
    {
        $this->line("\n[Testing Scenario: {$label}]");
        try {
            $result = $service->detectFraudRisk($tx);
            $this->line("Recommended Action/Decision: " . strtoupper($result['decision']));
            $this->line("Risk Score: " . $result['risk_score']);
            
            // Map decision to risk level
            $riskLevel = match($result['decision']) {
                'block' => 'High Risk',
                'review' => 'Medium Risk',
                default => 'Low Risk'
            };
            $this->line("Risk Level: " . $riskLevel);
            $this->line("Triggered Fraud Indicators/Flags: " . (empty($result['flags']) ? 'None' : implode(', ', $result['flags'])));
            $this->line("Sift score details: " . ($result['sift']['score'] ?? 0) . " (Reported: " . ($result['sift']['reported'] ? 'Yes' : 'No') . ")");
        } catch (\Throwable $e) {
            $this->error("❌ Failed during detectFraudRisk() testing: " . $e->getMessage());
        }
    }

    private function verifyProviderAPIs(FraudDetectionService $service)
    {
        $this->info("\n[A] Verifying verifyPaystackTransaction('test_paystack_ref_000'):");
        try {
            // Since this is a test, let's catch the network request failure and verify gracefulness
            $service->verifyPaystackTransaction('test_paystack_ref_000');
        } catch (\Throwable $e) {
            $this->warn("✔ Paystack transaction verify failed as expected (No live transaction exists): " . $e->getMessage());
        }

        $this->info("\n[B] Verifying verifyFlutterwaveTransaction('test_flutterwave_id_111'):");
        try {
            $service->verifyFlutterwaveTransaction('test_flutterwave_id_111');
        } catch (\Throwable $e) {
            $this->warn("✔ Flutterwave transaction verify failed as expected (No live transaction exists): " . $e->getMessage());
        }

        $this->info("\n[C] Verifying getTransactionDetails('test_id'):");
        try {
            // Will fallback appropriately
            $service->getTransactionDetails('test_id', 'paystack');
        } catch (\Throwable $e) {
            $this->warn("✔ getTransactionDetails caught gracefully as expected: " . $e->getMessage());
        }
    }

    private function runFailureTesting(FraudDetectionService $service)
    {
        $this->info("\n[A] Simulating SIFT_API_KEY not configured:");
        $originalSiftKey = config('fraud.sift.api_key');
        Config::set('fraud.sift.api_key', null);

        $mockTx = [
            'user_id' => 42,
            'email' => 'good_user@example.com',
            'amount' => 1500,
            'currency' => 'NGN',
            'reference' => 'test_ref_sift_missing',
            'provider' => 'paystack',
            'ip' => '127.0.0.1'
        ];

        try {
            $result = $service->detectFraudRisk($mockTx);
            $this->line("✔ Service handled missing SIFT_API_KEY gracefully. Sift Score: " . ($result['sift']['score'] ?? 'N/A') . " (Reported: " . ($result['sift']['reported'] ? 'Yes' : 'No') . ")");
        } catch (\Throwable $e) {
            $this->error("❌ Service crashed when SIFT_API_KEY is missing: " . $e->getMessage());
        }
        Config::set('fraud.sift.api_key', $originalSiftKey);

        $this->info("\n[B] Simulating complete provider network/API failure during detection:");
        // The service has try-catch surrounding the initial provider transaction enrichment step. Let's make sure it doesn't crash:
        $mockBrokenTx = [
            'user_id' => 12,
            'email' => 'broken_provider@example.com',
            'amount' => 1000,
            'reference' => 'nonexistent_or_malformed_reference_xyz',
            'provider' => 'invalid_provider_name_to_trigger_exception',
            'ip' => '8.8.8.8'
        ];

        try {
            $result = $service->detectFraudRisk($mockBrokenTx);
            $this->line("✔ Service handled invalid/failed provider details lookup gracefully.");
            $this->line("Recommended Action/Decision: " . strtoupper($result['decision']));
        } catch (\Throwable $e) {
            $this->error("❌ Service crashed during provider lookup failure simulation: " . $e->getMessage());
        }
    }
}

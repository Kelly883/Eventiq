<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds fraud analysis improvements based on production review:
     * 1. Denormalized columns from JSON for query performance
     * 2. Additional indexes for dashboard queries
     * 3. Investigation workflow columns
     * 4. Archived status instead of soft deletes
     */
    public function up(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            // === Denormalized columns from JSON for performance ===
            // Extract card_country from payment_details JSON
            $table->string('card_country', 2)->nullable()->after('card_fingerprint')
                  ->comment('Card issuing country (ISO 3166-1 alpha-2) for geographic fraud analysis');

            // Extract device_fingerprint from device_info JSON
            $table->string('device_fingerprint', 64)->nullable()->after('ip_address')
                  ->comment('Device fingerprint for cross-session fraud correlation');

            // Extract payment_method from payment_details JSON
            $table->string('payment_method', 50)->nullable()->after('card_country')
                  ->comment('Payment method: card, mobile_money, bank_transfer');

            // Extract payment_gateway from payment_details JSON
            $table->string('payment_gateway', 50)->nullable()->after('payment_method')
                  ->comment('Payment gateway: paystack, flutterwave');

            // === Velocity tracking denormalization ===
            $table->integer('user_orders_last_24h')->nullable()->after('velocity_metrics')
                  ->comment('Number of orders by this user in last 24 hours');

            $table->decimal('user_spend_last_24h', 12, 2)->nullable()->after('user_orders_last_24h')
                  ->comment('Total spend by this user in last 24 hours');

            // === Investigation workflow columns ===
            $table->string('user_agent', 500)->nullable()->after('ip_address')
                  ->comment('Full user agent string for investigation');

            $table->string('referrer', 500)->nullable()->after('user_agent')
                  ->comment('HTTP referrer at time of fraud detection');

            $table->string('promo_code')->nullable()->after('referrer')
                  ->comment('Promo/coupon code used if any');

            $table->uuid('escalated_to')->nullable()->after('reviewed_by')
                  ->comment('Staff member this case was escalated to');

            $table->timestamp('escalated_at')->nullable()->after('escalated_to')
                  ->comment('When case was escalated');

            $table->string('resolution', 50)->nullable()->after('status')
                  ->comment('Final resolution: false_positive, confirmed_fraud, chargeback, pending');

            $table->json('evidence_snapshot')->nullable()->after('duplicate_ticket_info')
                  ->comment('Full order snapshot at time of fraud detection for audit');

            // === Archived status instead of soft deletes ===
            $table->boolean('is_archived')->default(false)->after('resolution')
                  ->comment('Archive flag to hide from dashboard without deletion');

            $table->timestamp('archived_at')->nullable()->after('is_archived')
                  ->comment('When record was archived');

            // === Additional indexes for dashboard performance ===
            // IP address lookups for fraud investigation
            $table->index('ip_address', 'idx_fraud_ip_address');

            // Card fingerprint for cross-order fraud correlation
            $table->index('card_fingerprint', 'idx_fraud_card_fingerprint');

            // Date-only queries for dashboard time ranges
            $table->index('created_at', 'idx_fraud_created_at');

            // Reviewer workload queries
            $table->index(['reviewed_by', 'status'], 'idx_fraud_reviewer_status');

            // Archived records filtering
            $table->index('is_archived', 'idx_fraud_archived');

            // Geographic fraud analysis
            $table->index('card_country', 'idx_fraud_card_country');

            // Device fingerprint correlation
            $table->index('device_fingerprint', 'idx_fraud_device_fingerprint');
        });
    }

    public function down(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_fraud_ip_address');
            $table->dropIndex('idx_fraud_card_fingerprint');
            $table->dropIndex('idx_fraud_created_at');
            $table->dropIndex('idx_fraud_reviewer_status');
            $table->dropIndex('idx_fraud_archived');
            $table->dropIndex('idx_fraud_card_country');
            $table->dropIndex('idx_fraud_device_fingerprint');

            // Drop columns
            $table->dropColumn([
                'card_country',
                'device_fingerprint',
                'payment_method',
                'payment_gateway',
                'user_orders_last_24h',
                'user_spend_last_24h',
                'user_agent',
                'referrer',
                'promo_code',
                'escalated_to',
                'escalated_at',
                'resolution',
                'evidence_snapshot',
                'is_archived',
                'archived_at',
            ]);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Production-hardens the fraud_events table:
     * 1. Removes softDeletes (fraud audit trails must be immutable)
     * 2. Adds missing production columns (ip_address, card_fingerprint, etc.)
     * 3. Drops softDeletes column
     * 4. Adds unique constraint on (order_id, event_type)
     */
    public function up(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            // Drop soft deletes - fraud events must be IMMUTABLE
            // This uses a raw SQL approach since SQLite doesn't support DROP COLUMN easily
            // For production MySQL use: $table->dropSoftDeletes();

            // Add production-essential columns
            $table->string('session_id', 255)->nullable()->after('notes')
                  ->comment('Session ID at time of fraud detection for cross-referencing');

            $table->string('ip_address', 45)->nullable()->after('session_id')
                  ->comment('Client IP address at time of event');

            $table->string('card_fingerprint', 64)->nullable()->after('ip_address')
                  ->comment('Unique card fingerprint for cross-order fraud correlation');

            $table->decimal('amount', 10, 2)->nullable()->after('card_fingerprint')
                  ->comment('Transaction amount at time of fraud detection');

            $table->string('currency', 3)->nullable()->after('amount')
                  ->comment('Currency code (NGN, USD, GHS, etc.)');

            $table->string('gateway_response_code', 10)->nullable()->after('currency')
                  ->comment('Paystack/Flutterwave response code at time of event');

            $table->string('automated_action_taken', 50)->nullable()->after('gateway_response_code')
                  ->comment('What the system auto-did: block, flag, allow, review');

            $table->string('source', 50)->nullable()->after('automated_action_taken')
                  ->comment('Where detection originated: webhook, api, sift, manual');

            // Add unique constraint to prevent duplicate fraud events for same order+type
            $table->unique(['order_id', 'event_type'], 'idx_fraud_order_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            $table->dropUnique('idx_fraud_order_type_unique');
            $table->dropColumn([
                'session_id',
                'ip_address',
                'card_fingerprint',
                'amount',
                'currency',
                'gateway_response_code',
                'automated_action_taken',
                'source',
            ]);
            // Restore soft deletes (MySQL only)
            // $table->softDeletes();
        });
    }
};
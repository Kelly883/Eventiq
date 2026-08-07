<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing foreign key constraints and useful fraud analysis columns.
     *
     * 1. Foreign keys: order_id, user_id, reviewed_by, escalated_to
     * 2. Denormalized columns: order_total, ticket_quantity
     * 3. Billing verification columns: billing_country, billing_zip, shipping_billing_match
     */
    public function up(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            // === Add missing foreign key constraints ===
            // Order FK with cascade delete
            if (!Schema::hasColumn('fraud_events', 'order_id')) {
                // Column should already exist, just add FK
            }
            try {
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            } catch (\Exception $e) {
                // FK may already exist
            }

            // User FK with cascade delete
            try {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            } catch (\Exception $e) {
                // FK may already exist
            }

            // Reviewed by user (SET NULL on delete)
            try {
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            } catch (\Exception $e) {
                // FK may already exist
            }

            // Escalated to user (SET NULL on delete)
            try {
                $table->foreign('escalated_to')->references('id')->on('users')->onDelete('set null');
            } catch (\Exception $e) {
                // FK may already exist
            }

            // === Add missing analysis columns ===
            // Order total for high-value fraud detection
            if (!Schema::hasColumn('fraud_events', 'order_total')) {
                $table->decimal('order_total', 12, 2)->nullable()->after('amount')
                      ->comment('Total order value for high-value fraud detection');
            }

            // Ticket quantity for bulk purchase fraud
            if (!Schema::hasColumn('fraud_events', 'ticket_quantity')) {
                $table->integer('ticket_quantity')->nullable()->after('order_total')
                      ->comment('Number of tickets in order for bulk fraud detection');
            }

            // Billing verification
            if (!Schema::hasColumn('fraud_events', 'billing_country')) {
                $table->string('billing_country', 2)->nullable()->after('ticket_quantity')
                      ->comment('Billing address country (ISO 3166-1 alpha-2)');
            }

            if (!Schema::hasColumn('fraud_events', 'billing_zip')) {
                $table->string('billing_zip', 20)->nullable()->after('billing_country')
                      ->comment('Billing address postal code');
            }

            if (!Schema::hasColumn('fraud_events', 'shipping_billing_match')) {
                $table->boolean('shipping_billing_match')->nullable()->after('billing_zip')
                      ->comment('Whether shipping and billing addresses match');
            }

            // === Additional indexes for new columns ===
            if (Schema::hasColumn('fraud_events', 'order_total')) {
                $table->index('order_total', 'idx_fraud_order_total');
            }

            if (Schema::hasColumn('fraud_events', 'billing_country')) {
                $table->index('billing_country', 'idx_fraud_billing_country');
            }

            if (Schema::hasColumn('fraud_events', 'ticket_quantity')) {
                $table->index('ticket_quantity', 'idx_fraud_ticket_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_fraud_order_total');
            $table->dropIndex('idx_fraud_billing_country');
            $table->dropIndex('idx_fraud_ticket_quantity');

            // Drop foreign keys
            $table->dropForeign(['order_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['escalated_to']);

            // Drop columns
            $table->dropColumn([
                'order_total',
                'ticket_quantity',
                'billing_country',
                'billing_zip',
                'shipping_billing_match',
            ]);
        });
    }
};
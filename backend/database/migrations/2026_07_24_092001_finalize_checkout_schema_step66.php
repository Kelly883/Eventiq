<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Final schema fixes for Step 66 verification:
     * 1. Add gateway_transaction_id to orders & payments (Paystack: data.id, Flutterwave: data.id)
     * 2. Add settlement_id, settled_at to payments for organizer payout reconciliation
     * 3. Add subtotal, tax_amount, discount_amount, coupon_code to orders for accounting
     * 4. Add billing_name, billing_email, billing_phone to orders
     * 5. Add idempotency_key to payments for duplicate webhook protection
     * 6. Add refunded_by user tracking to payments
     * 7. Add created_at indexes to orders and payments for time-range queries
     * 8. Add partially_refunded to order status
     * 9. Add payment_id FK to refund_requests for direct refund↔payment linkage
     * 10. Update PaymentGatewayService to use payment_intent_id
     */
    public function up(): void
    {
        // ============ ORDERS TABLE ============
        if (Schema::hasTable('orders')) {
            // Gateway transaction ID (returned after payment verification)
            if (!Schema::hasColumn('orders', 'gateway_transaction_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->string('gateway_transaction_id', 100)->nullable()->after('payment_intent_id');
                });
            }

            // Accounting fields
            if (!Schema::hasColumn('orders', 'subtotal')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->decimal('subtotal', 10, 2)->nullable()->after('total_amount');
                });
            }
            if (!Schema::hasColumn('orders', 'tax_amount')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->decimal('tax_amount', 10, 2)->nullable()->after('subtotal');
                });
            }
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->decimal('discount_amount', 10, 2)->default(0)->after('tax_amount');
                });
            }
            if (!Schema::hasColumn('orders', 'coupon_code')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->string('coupon_code', 50)->nullable()->after('discount_amount');
                });
            }

            // Billing address
            if (!Schema::hasColumn('orders', 'billing_name')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->string('billing_name', 255)->nullable()->after('coupon_code');
                });
            }
            if (!Schema::hasColumn('orders', 'billing_email')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->string('billing_email', 255)->nullable()->after('billing_name');
                });
            }
            if (!Schema::hasColumn('orders', 'billing_phone')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->string('billing_phone', 50)->nullable()->after('billing_email');
                });
            }

            // Add created_at index for time-range queries
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index('created_at', 'idx_orders_created_at');
                });
            } catch (\Exception $e) {
                // Index already exists, skip
            }
        }

        // ============ PAYMENTS TABLE ============
        if (Schema::hasTable('payments')) {
            // Gateway transaction ID (the actual transaction ID post-verification)
            if (!Schema::hasColumn('payments', 'gateway_transaction_id')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('gateway_transaction_id', 100)->nullable()->after('payment_intent_id');
                });
            }

            // Settlement/payout reconciliation
            if (!Schema::hasColumn('payments', 'settlement_id')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('settlement_id', 100)->nullable()->after('gateway_transaction_id');
                    $table->timestamp('settled_at')->nullable()->after('settlement_id');
                });
            }

            // Idempotency key for webhook deduplication
            if (!Schema::hasColumn('payments', 'idempotency_key')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('idempotency_key', 100)->nullable()->unique()->after('gateway');
                });
            }

            // Refund tracking - who processed the refund
            if (!Schema::hasColumn('payments', 'refunded_by')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->uuid('refunded_by')->nullable()->after('refunded_at');
                });
            }

            // Add created_at index for reconciliation date ranges
            try {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index('created_at', 'idx_payments_created_at');
                });
            } catch (\Exception $e) {
                // Index already exists
            }

            // Add composite index for reconciliation queries
            try {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index(['gateway', 'status', 'created_at'], 'idx_payments_gateway_status_date');
                });
            } catch (\Exception $e) {
                // Index already exists
            }
        }

        // ============ REFUND REQUESTS TABLE ============
        // Add direct payment_id FK linkage (newer migration 2026_07_22_074001
        // already added order_id, event_id, etc but no direct payment_id)
        if (Schema::hasTable('refund_requests') && !Schema::hasColumn('refund_requests', 'payment_id')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->uuid('payment_id')->nullable()->after('event_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $columns = ['gateway_transaction_id', 'subtotal', 'tax_amount', 'discount_amount',
                    'coupon_code', 'billing_name', 'billing_email', 'billing_phone'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('orders', $col)) {
                        $table->dropColumn($col);
                    }
                }
                $table->dropIndex('idx_orders_created_at');
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $columns = ['gateway_transaction_id', 'settlement_id', 'settled_at',
                    'idempotency_key', 'refunded_by'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('payments', $col)) {
                        $table->dropColumn($col);
                    }
                }
                $table->dropIndex('idx_payments_created_at');
                $table->dropIndex('idx_payments_gateway_status_date');
            });
        }

        if (Schema::hasTable('refund_requests') && Schema::hasColumn('refund_requests', 'payment_id')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropColumn('payment_id');
            });
        }
    }
};


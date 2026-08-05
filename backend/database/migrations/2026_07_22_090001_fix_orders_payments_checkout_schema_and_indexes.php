<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fixes identified issues in Step 66 verification:
     * 1. Add soft deletes to orders table
     * 2. Add missing indexes on orders(event_id) and orders(user_id, status)
     * 3. Add refund tracking fields to payments table
     * 4. Ensure payment_intent_id column exists on orders (if Set A schema was used)
     * 5. Rename gateway_reference to payment_intent_id on payments if needed
     * 6. Add composite index for user dashboard queries
     * 7. Add missing indexes on tickets(user_id), tickets(order_id), tickets(order_id, ticket_tier_id)
     * 8. Add missing index on order_items(ticket_tier_id)
     */
    public function up(): void
    {
        // ============ ORDERS TABLE FIXES ============

        // Add soft deletes
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'deleted_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Ensure payment_intent_id exists (if old Set A schema was used with payment_reference)
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_reference') && !Schema::hasColumn('orders', 'payment_intent_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->renameColumn('payment_reference', 'payment_intent_id');
            });
        }

        // Add missing indexes - use try/catch since SQLite doesn't support Doctrine
        if (Schema::hasTable('orders')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index('event_id', 'idx_orders_event_id');
                });
            } catch (\Exception $e) {
                // Index already exists, skip
            }

            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index(['user_id', 'status'], 'idx_orders_user_status');
                });
            } catch (\Exception $e) {
                // Index already exists, skip
            }
        }

        // ============ PAYMENTS TABLE FIXES ============

        // Handle column rename before any other payment changes
        $paymentsHasGatewayRef = Schema::hasTable('payments') && Schema::hasColumn('payments', 'gateway_reference') && !Schema::hasColumn('payments', 'payment_intent_id');
        if ($paymentsHasGatewayRef) {
            Schema::table('payments', function (Blueprint $table) {
                $table->renameColumn('gateway_reference', 'payment_intent_id');
            });
        }

        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'refunded_amount')) {
            Schema::table('payments', function (Blueprint $table) {
                // Refund tracking
                $table->decimal('refunded_amount', 10, 2)->nullable()->after('amount');
                $table->timestamp('refunded_at')->nullable()->after('status');
                $table->text('failure_reason')->nullable()->after('gateway_response');

                // Transaction fees
                $table->decimal('fee', 10, 2)->nullable()->after('amount');
                $table->string('failure_code', 50)->nullable()->after('failure_reason');

                // Index for payment status queries
                $table->index('status', 'idx_payments_status');
            });
        }

        // ============ TICKETS TABLE FIXES ============

        if (Schema::hasTable('tickets')) {
            try {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->index('user_id', 'idx_tickets_user_id');
                });
            } catch (\Exception $e) {
                // Index already exists, skip
            }

            try {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->index('order_id', 'idx_tickets_order_id');
                });
            } catch (\Exception $e) {
                // Index already exists, skip
            }

            try {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->index(['order_id', 'ticket_tier_id'], 'idx_tickets_order_tier');
                });
            } catch (\Exception $e) {
                // Index already exists, skip
            }
        }

        // ============ ORDER ITEMS TABLE FIXES ============

        if (Schema::hasTable('order_items')) {
            try {
                Schema::table('order_items', function (Blueprint $table) {
                    $table->index('ticket_tier_id', 'idx_order_items_ticket_tier_id');
                });
            } catch (\Exception $e) {
                // Index already exists, skip
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Orders table
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'deleted_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropSoftDeletes();
                $table->dropIndex('idx_orders_event_id');
                $table->dropIndex('idx_orders_user_status');
            });
        }

        // Payments table
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'refunded_amount')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn([
                    'refunded_amount',
                    'refunded_at',
                    'failure_reason',
                    'fee',
                    'failure_code',
                ]);
                $table->dropIndex('idx_payments_status');
            });
        }

        // Tickets table
        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropIndex('idx_tickets_user_id');
                $table->dropIndex('idx_tickets_order_id');
                $table->dropIndex('idx_tickets_order_tier');
            });
        }

        // Order items table
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropIndex('idx_order_items_ticket_tier_id');
            });
        }
    }
};


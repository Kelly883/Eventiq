<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 66 production readiness reconciliation.
 *
 * The development SQLite database ended up with INTEGER/auto-increment
 * schemas for orders, order_items, tickets, and payments (a legacy
 * migration rebuilt the orders table with the wrong column types, so
 * the four Step 66 tables - and the FK columns in the empty dependent
 * tables that reference them - drifted from the PRD's UUID layout).
 *
 * All affected tables are EMPTY at this point, so the schema is rebuilt
 * safely to the PRD-specified layout:
 *
 *   - orders, order_items, tickets, payments -> UUID primary keys
 *   - user_id / order_id FK columns -> TEXT (UUID) to match users/orders
 *   - event_id / ticket_tier_id FK columns -> BIGINT to match events/ticket_tiers
 *   - All required indexes and unique constraints restored
 *
 * This migration is SQLite-specific. On MySQL / PostgreSQL the
 * 2026_08_05_0660xx_step66_ensure_* migrations already produce the
 * correct UUID schema; SQLite is the only engine that allowed the
 * type drift to occur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        foreach (['orders', 'order_items', 'tickets', 'payments'] as $table) {
            if (! Schema::hasTable($table)) {
                return; // Fresh DB - the create migrations handle everything.
            }
        }

        // Confirm all four Step 66 tables are empty before rebuilding.
        foreach (['orders', 'order_items', 'tickets', 'payments'] as $table) {
            if (DB::table($table)->exists()) {
                throw new \RuntimeException(
                    "Step 66 reconciliation refused: {$table} is not empty."
                );
            }
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::transaction(function () {
                $this->dropTable('payments');
                $this->dropTable('tickets');
                $this->dropTable('order_items');
                $this->dropTable('orders');

                $this->rebuildOrders();
                $this->rebuildOrderItems();
                $this->rebuildTickets();
                $this->rebuildPayments();
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down(): void
    {
        // Intentionally no-op: there is no meaningful previous state.
    }

    private function dropTable(string $table): void
    {
        try {
            Schema::dropIfExists($table);
        } catch (\Throwable) {
            DB::statement('PRAGMA foreign_keys = OFF');
            Schema::dropIfExists($table);
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function rebuildOrders(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_gateway')->nullable();
            $table->string('payment_intent_id')->nullable();
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->string('device_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('tax_amount', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('coupon_code', 50)->nullable();
            $table->string('billing_name', 255)->nullable();
            $table->string('billing_email', 255)->nullable();
            $table->string('billing_phone', 50)->nullable();
            $table->text('failure_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');

            $table->index('user_id', 'orders_user_id_index');
            $table->index('status', 'orders_status_index');
            $table->index('event_id', 'orders_event_id_index');
            $table->unique('payment_intent_id', 'orders_payment_intent_id_unique');
            $table->index(['user_id', 'status'], 'idx_orders_user_status');
            $table->index('event_id', 'idx_orders_event_id');
            $table->index('created_at', 'idx_orders_created_at');
            $table->index(['event_id', 'created_at'], 'idx_orders_event_created_at');
            $table->index(['user_id', 'created_at'], 'idx_orders_user_created_at');
        });
    }

    private function rebuildOrderItems(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->unsignedBigInteger('ticket_tier_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('ticket_tier_id')->references('id')->on('ticket_tiers')->onDelete('cascade');

            $table->index('order_id', 'order_items_order_id_index');
            $table->index('order_id', 'idx_order_items_order_id');
            $table->index('ticket_tier_id', 'idx_order_items_ticket_tier_id');
        });
    }

    private function rebuildTickets(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('user_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('ticket_tier_id');

            $table->text('qr_code_data')->nullable();
            $table->string('qr_code_secret')->nullable();
            $table->timestamp('qr_code_generated_at')->nullable();
            $table->timestamp('qr_code_expires_at')->nullable();
            $table->enum('status', ['valid', 'checked_in', 'void'])->default('valid');
            $table->timestamp('checked_in_at')->nullable();
            $table->uuid('checked_in_by')->nullable();
            $table->integer('qr_code_scanned_count')->default(0);
            $table->timestamp('last_qr_scan_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('ticket_tier_id')->references('id')->on('ticket_tiers')->onDelete('cascade');
            $table->foreign('checked_in_by')->references('id')->on('users')->onDelete('set null');

            $table->index('user_id', 'tickets_user_id_index');
            $table->index('event_id', 'tickets_event_id_index');
            $table->index('user_id', 'idx_tickets_user_id');
            $table->index('order_id', 'idx_tickets_order_id');
            $table->index(['event_id', 'status'], 'idx_tickets_event_status');
            $table->index(['event_id', 'checked_in_at'], 'idx_tickets_event_checkin');
            $table->index(['event_id', 'created_at'], 'idx_tickets_event_created_at');
            $table->index('qr_code_expires_at');
        });
    }

    private function rebuildPayments(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->string('payment_intent_id')->nullable();
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('status');
            $table->string('gateway');
            $table->string('idempotency_key', 100)->nullable();
            $table->json('gateway_response')->nullable();
            $table->decimal('fees', 10, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->nullable();
            $table->uuid('refunded_by')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->string('settlement_id', 100)->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_brand', 50)->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('refunded_by')->references('id')->on('users')->onDelete('set null');

            $table->index('order_id', 'payments_order_id_index');
            $table->index('order_id', 'idx_payments_order_id');
            $table->index('created_at', 'idx_payments_created_at');
            $table->index('payment_intent_id', 'idx_payments_payment_intent_id');
            $table->index(['gateway', 'status', 'created_at'], 'idx_payments_gateway_status_date');
            $table->index(['order_id', 'status', 'created_at'], 'idx_payments_order_status_created_at');
            $table->unique('payment_intent_id', 'uq_payments_payment_intent_id');
        });
    }
};

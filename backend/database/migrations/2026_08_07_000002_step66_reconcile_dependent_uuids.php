<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 66 production readiness - dependent table FK reconciliation.
 *
 * The Step 66 tables (orders, tickets) now use UUID primary keys, but the
 * empty dependent tables that reference them (check_ins, refund_requests,
 * delivery_events) were left with INTEGER FK columns from an earlier
 * incorrect SQLite schema. Because every affected table is empty, the
 * FKs are rebuilt to TEXT (UUID) so foreign-key integrity holds at the
 * database level.
 *
 * SQLite-only; other engines already carry the correct UUID layout.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        foreach (['check_ins', 'refund_requests', 'delivery_events'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new \RuntimeException(
                    "Step 66 dependent reconciliation refused: {$table} is not empty."
                );
            }
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::transaction(function () {
                $this->rebuildCheckIns();
                $this->rebuildRefundRequests();
                $this->rebuildDeliveryEvents();
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down(): void
    {
        // Intentionally no-op.
    }

    private function rebuildCheckIns(): void
    {
        Schema::dropIfExists('check_ins');

        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->uuid('ticket_id');
            $table->uuid('user_id')->nullable();
            $table->timestamp('checked_in_at');
            $table->string('client_mutation_id')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index('ticket_id');
            $table->index('user_id');
        });
    }

    private function rebuildRefundRequests(): void
    {
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('refund_appeals');

        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('ticket_id');
            $table->uuid('user_id');
            $table->unsignedBigInteger('event_id')->nullable();
            $table->unsignedBigInteger('refund_policy_id')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('requested_amount', 10, 2);
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->text('reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->string('payment_gateway_refund_id')->nullable();
            $table->json('payment_gateway_response')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
            $table->foreign('refund_policy_id')->references('id')->on('refund_policies')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');

            $table->index('ticket_id');
            $table->index('user_id');
            $table->index(['user_id', 'status'], 'idx_refund_user_status');
            $table->index(['event_id', 'status'], 'idx_refund_event_status');
        });

        Schema::create('refund_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_request_id')->constrained()->cascadeOnDelete();
            $table->uuid('user_id');
            $table->text('reason')->nullable();
            $table->string('appeal_reason')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamps();
        });
    }

    private function rebuildDeliveryEvents(): void
    {
        Schema::dropIfExists('delivery_events');

        Schema::create('delivery_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('ticket_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->uuid('order_id')->nullable();

            $table->string('channel');
            $table->string('status')->default('pending');
            $table->string('ticket_reference')->nullable();
            $table->string('recipient')->nullable();
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('sender')->nullable();
            $table->json('payload')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->text('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');

            $table->index('ticket_id');
            $table->index('user_id');
            $table->index('event_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('channel');
            $table->index(['status', 'channel']);
            $table->index('provider_message_id');
            $table->index('created_at');
        });
    }
};
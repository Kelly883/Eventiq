<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactions')) {
            return;
        }

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('organizer_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->uuid('event_id')->nullable();
            $table->uuid('ticket_id')->nullable();
            $table->string('gateway');
            $table->string('reference');
            $table->string('gateway_transaction_id')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('authorization_code')->nullable();
            $table->string('authorization_type')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->decimal('fees', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->string('status');
            $table->string('payment_channel')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_code')->nullable();
            $table->json('gateway_response')->nullable();
            $table->text('last_error')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->string('refund_reference')->nullable();
            $table->boolean('is_fully_refunded')->default(false);
            $table->string('webhook_event_id')->nullable();
            $table->string('webhook_idempotency_key')->nullable()->unique();
            $table->integer('attempts')->default(1);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('organizer_id')->references('id')->on('organizers')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');

            $table->index('user_id', 'idx_transactions_user_id');
            $table->index('organizer_id', 'idx_transactions_organizer_id');
            $table->index(['gateway', 'status'], 'idx_transactions_gateway_status');
            $table->index('reference', 'idx_transactions_reference');
            $table->index('webhook_event_id', 'idx_transactions_webhook_event_id');
            $table->index(['created_at'], 'idx_transactions_created_at');
            $table->unique(['reference', 'gateway'], 'idx_transactions_reference_gateway_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

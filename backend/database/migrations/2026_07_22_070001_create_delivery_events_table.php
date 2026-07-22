<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_events')) {
            return;
        }

        Schema::create('delivery_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('order_id');
            $table->uuid('user_id');
            $table->uuid('event_id');
            $table->enum('delivery_method', ['email', 'sms', 'dashboard']);
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'bounced', 'viewed']);
            $table->timestamp('delivery_timestamp')->nullable();
            $table->timestamp('viewed_timestamp')->nullable();
            $table->integer('attempt_count')->default(1);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->json('provider_response')->nullable();
            $table->uuid('fraud_event_id')->nullable();
            $table->boolean('delivery_blocked')->default(false);
            $table->string('block_reason')->nullable();
            $table->text('qr_code_data');
            $table->string('ticket_pdf_url')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('order_id');
            $table->index('user_id');
            $table->index('event_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_events');
    }
};
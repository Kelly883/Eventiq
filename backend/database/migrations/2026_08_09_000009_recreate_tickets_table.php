<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('tickets');

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
            $table->enum('status', ['valid', 'checked_in', 'void', 'purged'])->default('valid');
            $table->timestamp('checked_in_at')->nullable();
            $table->uuid('checked_in_by')->nullable();
            $table->integer('qr_code_scanned_count')->default(0);
            $table->timestamp('last_qr_scan_at')->nullable();
            $table->timestamps();

            $table->string('ticket_id')->nullable();
            $table->string('attendee_name')->nullable();
            $table->string('attendee_email')->nullable();
            $table->string('tier')->nullable();
            $table->boolean('checked_in')->default(false);
            $table->timestamp('first_scanned_at')->nullable();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreign('ticket_tier_id')->references('id')->on('ticket_tiers')->cascadeOnDelete();
            $table->foreign('checked_in_by')->references('id')->on('users')->nullOnDelete();

            $table->index('user_id', 'tickets_user_id_index');
            $table->index('event_id', 'tickets_event_id_index');
            $table->index(['event_id', 'status'], 'idx_tickets_event_status');
            $table->index(['event_id', 'checked_in_at'], 'idx_tickets_event_checkin');
            $table->index(['event_id', 'created_at'], 'idx_tickets_event_created_at');
            $table->index('ticket_id', 'idx_tickets_ticket_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

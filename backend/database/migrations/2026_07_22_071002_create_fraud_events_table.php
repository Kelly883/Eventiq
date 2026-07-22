<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('fraud_events');

        Schema::create('fraud_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('event_id');
            $table->enum('fraud_type', ['duplicate_checkin', 'invalid_qr', 'manual_override']);
            $table->timestamp('detected_at');
            $table->timestamp('first_check_in_at')->nullable();
            $table->uuid('first_check_in_by')->nullable();
            $table->timestamp('second_check_in_at')->nullable();
            $table->uuid('second_check_in_by')->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high']);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('first_check_in_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('second_check_in_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['ticket_id', 'event_id'], 'idx_fraud_ticket_event');
            $table->index(['event_id', 'detected_at'], 'idx_fraud_event_detected');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_events');
    }
};
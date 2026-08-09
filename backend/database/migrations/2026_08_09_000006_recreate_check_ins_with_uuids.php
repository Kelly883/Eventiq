<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('check_ins');

        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->uuid('ticket_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('event_id');
            $table->uuid('scanned_by')->nullable();

            $table->string('status')->default('checked_in');
            $table->string('device_type')->nullable();
            $table->string('device_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('qr_verified')->default(true);
            $table->text('failure_reason')->nullable();
            $table->timestamp('checked_in_at');
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreign('scanned_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['ticket_id', 'checked_in_at'], 'uq_checkins_ticket_scanned');
            $table->index(['event_id', 'checked_in_at'], 'idx_checkins_event_scanned');
            $table->index('scanned_by', 'idx_checkins_scanned_by');
            $table->index('checked_in_at', 'idx_checkins_scanned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};

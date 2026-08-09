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
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('checked_in');
            $table->string('device_type')->nullable();
            $table->string('device_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('qr_verified')->default(true);
            $table->text('failure_reason')->nullable();
            $table->timestamp('checked_in_at');
            $table->timestamps();

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

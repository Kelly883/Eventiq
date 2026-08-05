<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates a dedicated check_ins audit table. Each row represents one
     * scan/check-in event for a ticket, preserving full history instead
     * of overwriting checked_in_at on the tickets table.
     */
    public function up(): void
    {
        if (Schema::hasTable('check_ins')) {
            return;
        }

        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();

            // ── Foreign Keys ──────────────────────────────────────────
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            // `event_id` is duplicated here for fast event-level analytics
            // (e.g., "show me all check-ins for event X") without joining
            // through tickets.
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            // `user_id` is the attendee who owns the ticket
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // `scanned_by` is the staff member or device that performed the scan
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();

            // ── Scan Context ──────────────────────────────────────────
            $table->string('status')->default('checked_in'); // checked_in | failed | duplicate | expired
            $table->string('device_type')->nullable();        // mobile | kiosk | handheld
            $table->string('device_id')->nullable();          // device identifier
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // ── QR Verification ──────────────────────────────────────
            $table->boolean('qr_verified')->default(true);
            $table->text('failure_reason')->nullable();

            // ── Timestamps ───────────────────────────────────────────
            $table->timestamp('scanned_at');
            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────
            // Fast: "show all check-ins for event X ordered by time"
            $table->index(['event_id', 'scanned_at']);

            // Fast: "show all check-ins for ticket X"
            $table->index(['ticket_id', 'scanned_at']);

            // Fast: "show all scans performed by staff member Y"
            $table->index('scanned_by');

            // Fast: "show all scans across all events in a date range"
            $table->index('scanned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
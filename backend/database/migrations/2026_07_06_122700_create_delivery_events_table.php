<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * delivery_events records every ticket delivery attempt across all
     * channels (email, SMS, dashboard). This is an append-only audit log
     * — rows are never updated after final status is reached, and soft
     * deletes are intentionally omitted to preserve the full delivery
     * trail for compliance and debugging.
     */
    public function up(): void
    {
        Schema::create('delivery_events', function (Blueprint $table) {
            $table->id();

            // ── Foreign Keys ──────────────────────────────────────────
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            // user_id references the UUID-based users table
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            // ── Channel & Status ──────────────────────────────────────
            $table->string('channel');                    // email | sms | dashboard | push
            $table->string('status')->default('pending'); // pending | sent | delivered | failed | bounced | opened | clicked
            $table->string('ticket_reference')->nullable();

            // ── Content ───────────────────────────────────────────────
            $table->string('recipient')->nullable();      // email address or phone number
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('sender')->nullable();
            $table->json('payload')->nullable();          // arbitrary metadata from caller

            // ── Provider Details ──────────────────────────────────────
            $table->string('provider')->nullable();       // termii | smtp | dashboard
            $table->string('provider_message_id')->nullable()->index();
            $table->text('provider_response')->nullable();// raw response from provider
            $table->text('error_message')->nullable();

            // ── Retry / Attempt Tracking ──────────────────────────────
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->timestamp('last_attempt_at')->nullable();

            // ── Delivery Timeline ─────────────────────────────────────
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────
            $table->index('ticket_id');
            $table->index('user_id');
            $table->index('event_id');
            $table->index('status');
            $table->index('channel');
            $table->index(['status', 'channel']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_events');
    }
};

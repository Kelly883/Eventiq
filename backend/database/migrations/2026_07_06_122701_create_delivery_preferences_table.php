<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * delivery_preferences stores per-user notification/delivery settings.
     * The user_id is unique (one-to-one) — each user has exactly one
     * preferences row, created on first access via a service/repository.
     * Soft deletes are enabled so that preferences can be restored if a
     * user re-enables delivery after disabling it.
     */
    public function up(): void
    {
        Schema::create('delivery_preferences', function (Blueprint $table) {
            $table->id();

            // ── Foreign Key (one-to-one with users) ───────────────────
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // ── Channel Toggles ───────────────────────────────────────
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('dashboard_enabled')->default(true);
            $table->boolean('push_enabled')->default(false);

            // ── Channel Configuration ─────────────────────────────────
            $table->string('preferred_channel')->default('email'); // email | sms | dashboard | push
            $table->string('email_address')->nullable();
            $table->string('phone_number')->nullable();

            // ── Quiet Hours ───────────────────────────────────────────
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();

            // ── Rate Limiting ─────────────────────────────────────────
            $table->unsignedSmallInteger('max_daily_notifications')->default(10);

            // ── Locale ────────────────────────────────────────────────
            $table->string('language', 10)->default('en');
            $table->string('timezone', 64)->default('UTC');

            $table->timestamps();
            $table->softDeletes(); // audit trail for disable/re-enable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_preferences');
    }
};

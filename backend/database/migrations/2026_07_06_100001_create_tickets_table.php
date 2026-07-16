<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base tickets table. A later migration (2026_07_06_124000_add_qr_code_
 * fields_to_tickets_table.php) adds qr_code/checked_in/checked_in_at/
 * checked_in_by via Schema::table('tickets', ...) - this file did not
 * exist before, meaning that migration would have failed outright on a
 * fresh database ("table tickets doesn't exist"). Columns here are based
 * on actual usage in CheckInController and FraudDetectionService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_tier_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('valid'); // valid|cancelled|refunded
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

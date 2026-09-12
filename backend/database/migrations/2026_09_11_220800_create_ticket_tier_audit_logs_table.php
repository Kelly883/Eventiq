<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_tier_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tier_id')->nullable()->constrained('ticket_tiers')->nullOnDelete();
            $table->string('action'); // created, updated, deleted
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organizer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('changes')->nullable();
            $table->string('tier_name')->nullable();
            $table->decimal('tier_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_tier_audit_logs');
    }
};

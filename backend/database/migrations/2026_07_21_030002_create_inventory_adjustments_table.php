<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('inventory_adjustments');
        
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_tier_id')->constrained()->onDelete('cascade');
            $table->foreignId('pricing_window_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('organizer_id')->constrained('users')->onDelete('cascade');
            $table->enum('adjustment_type', ['manual_increase', 'manual_decrease', 'reallocation', 'system_correction']);
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->integer('quantity_delta');
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            // Indexes
            $table->index(['event_id', 'created_at'], 'idx_inventory_adjustments_event_created');
            $table->index('organizer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};

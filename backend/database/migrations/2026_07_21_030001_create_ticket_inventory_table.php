<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ticket_inventory');
        
        Schema::create('ticket_inventory', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_tier_id')->constrained()->onDelete('cascade');
            $table->integer('total_allocated')->default(0);
            $table->integer('total_sold')->default(0);
            $table->integer('total_available')->virtualAs('total_allocated - total_sold');
            $table->integer('low_stock_threshold')->nullable();
            $table->boolean('is_low_stock')->virtualAs('CASE WHEN total_available <= low_stock_threshold THEN 1 ELSE 0 END');
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('event_id');
            $table->index('ticket_tier_id');
            $table->index(['event_id', 'ticket_tier_id'], 'idx_ticket_inventory_event_tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_inventory');
    }
};

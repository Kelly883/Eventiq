<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('analytics_sales_timeline');
        
        Schema::create('analytics_sales_timeline', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_tier_id')->constrained()->onDelete('cascade');
            $table->foreignId('pricing_window_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('sale_timestamp');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('buyer_email', 255)->nullable();
            $table->string('source', 100)->nullable();
            $table->timestamp('created_at')->nullable();

            // Indexes for time-series queries
            $table->index('event_id');
            $table->index('sale_timestamp');
            $table->index(['event_id', 'sale_timestamp'], 'idx_sales_timeline_event_timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_sales_timeline');
    }
};

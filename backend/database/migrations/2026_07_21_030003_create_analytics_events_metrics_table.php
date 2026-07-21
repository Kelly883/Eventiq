<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('analytics_events_metrics');
        
        Schema::create('analytics_events_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('organizer_id')->constrained('organizers')->onDelete('cascade');
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->integer('total_tickets_sold')->default(0);
            $table->integer('total_page_views')->default(0);
            $table->integer('total_ticket_page_views')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('average_ticket_price', 10, 2)->default(0);
            $table->integer('peak_sales_hour')->nullable();
            $table->foreignId('top_ticket_tier_id')->nullable()->constrained('ticket_tiers')->onDelete('set null');
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('event_id');
            $table->index('organizer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events_metrics');
    }
};

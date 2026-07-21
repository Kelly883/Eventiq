<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('analytics_tier_performance');
        
        Schema::create('analytics_tier_performance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_tier_id')->constrained()->onDelete('cascade');
            $table->integer('total_sold')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('average_price', 10, 2)->default(0);
            $table->decimal('percentage_of_total_sales', 5, 2)->default(0);
            $table->decimal('percentage_of_total_revenue', 5, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('event_id');
            $table->index('ticket_tier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_tier_performance');
    }
};

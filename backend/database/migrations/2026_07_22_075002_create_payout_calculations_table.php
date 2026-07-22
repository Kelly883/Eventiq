<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_calculations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payout_id');
            $table->uuid('organizer_id');
            $table->timestamp('settlement_period_start_date');
            $table->timestamp('settlement_period_end_date');
            $table->json('event_ids')->nullable();
            $table->json('order_ids')->nullable();
            $table->json('refund_request_ids')->nullable();
            $table->integer('total_order_count')->default(0);
            $table->integer('total_tickets_sold')->default(0);
            $table->integer('total_refunds_processed')->default(0);
            $table->json('calculation_details')->nullable();
            $table->timestamp('calculated_at');
            $table->string('calculated_by')->nullable();
            $table->timestamp('created_at');

            $table->index('payout_id');
            $table->index('organizer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_calculations');
    }
};
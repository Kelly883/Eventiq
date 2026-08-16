<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payout_calculation_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payout_calculation_id');
            $table->uuid('event_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->uuid('refund_request_id')->nullable();
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('processing_fee_amount', 12, 2)->default(0);
            $table->decimal('tax_withholding_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->json('item_details')->nullable();
            $table->timestamps();

            $table->foreign('payout_calculation_id')->references('id')->on('payout_calculations')->cascadeOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->nullOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('refund_request_id')->references('id')->on('refund_requests')->nullOnDelete();

            $table->index('payout_calculation_id');
            $table->index('event_id');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_calculation_items');
    }
};

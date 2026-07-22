<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organizer_id');
            $table->timestamp('settlement_period_start_date');
            $table->timestamp('settlement_period_end_date');
            $table->decimal('gross_revenue', 12, 2);
            $table->decimal('refunds_deducted', 12, 2);
            $table->decimal('net_revenue', 12, 2);
            $table->decimal('platform_commission_percentage', 5, 2);
            $table->decimal('platform_commission_amount', 12, 2);
            $table->decimal('processing_fee_percentage', 5, 2);
            $table->decimal('processing_fee_amount', 12, 2);
            $table->decimal('tax_withholding_percentage', 5, 2)->nullable();
            $table->decimal('tax_withholding_amount', 12, 2)->nullable();
            $table->decimal('payout_amount', 12, 2);
            $table->string('payout_method');
            $table->string('payment_gateway_payout_id')->nullable();
            $table->json('payment_gateway_response')->nullable();
            $table->string('status'); // pending, calculated, approved, processing, completed, failed
            $table->timestamp('calculated_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index('organizer_id');
            $table->index('status');
            $table->index('settlement_period_start_date');
            $table->index('settlement_period_end_date');
            $table->index(['organizer_id', 'status'], 'idx_payouts_organizer_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
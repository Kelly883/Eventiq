<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('organizer_tier'); // standard, premium, enterprise
            $table->decimal('platform_commission_percentage', 5, 2);
            $table->decimal('processing_fee_percentage', 5, 2);
            $table->string('payout_frequency'); // daily, weekly, biweekly, monthly, on_demand
            $table->decimal('minimum_payout_threshold', 10, 2);
            $table->integer('payout_hold_days');
            $table->boolean('requires_approval')->default(false);
            $table->decimal('auto_approve_threshold', 10, 2)->nullable();
            $table->integer('max_retries')->default(3);
            $table->decimal('retry_backoff_multiplier', 3, 2)->default(1.5);
            $table->decimal('tax_withholding_percentage', 5, 2)->nullable();
            $table->json('allowed_payout_methods')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('organizer_tier', 'idx_settlement_organizer_tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_policies');
    }
};
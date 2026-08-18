<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('organizer_payouts')) {
            return;
        }

        Schema::create('organizer_payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organizer_id');
            $table->string('gateway');
            $table->string('reference');
            $table->string('status');
            $table->decimal('amount', 10, 2);
            $table->decimal('fees', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->json('metadata')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->uuid('initiated_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->uuid('settlement_id')->nullable();
            $table->timestamps();

            $table->foreign('organizer_id')->references('id')->on('organizers')->onDelete('cascade');
            $table->foreign('initiated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('settlement_id')->references('id')->on('payouts')->onDelete('set null');

            $table->index('organizer_id', 'idx_organizer_payouts_organizer_id');
            $table->index(['gateway', 'status'], 'idx_organizer_payouts_gateway_status');
            $table->index('reference', 'idx_organizer_payouts_reference');
            $table->index('settlement_id', 'idx_organizer_payouts_settlement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_payouts');
    }
};

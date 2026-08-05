<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('settlement_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('platform_fee_percentage', 5, 2)->default(0);
            $table->enum('payout_frequency', ['daily', 'weekly', 'biweekly', 'monthly', 'manual'])->default('monthly');
            $table->decimal('minimum_payout_amount', 12, 2)->default(0);
            $table->json('payment_methods')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active']);
            $table->index(['payout_frequency']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('settlement_policies');
    }
};

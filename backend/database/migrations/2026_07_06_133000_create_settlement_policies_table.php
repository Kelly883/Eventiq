<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Disable the implicit transaction wrapper for this migration.
    // On managed PostgreSQL (Render, RDS, etc.), a prior statement failure
    // in the same transaction aborts the entire transaction, causing
    // subsequent CREATE TABLE/ALTER TABLE to fail with "current transaction is aborted".
    // By opting out, each statement runs in its own autocommit transaction.
    public $withinTransaction = false;
    public function up()
    {
        if (Schema::hasTable('settlement_policies')) {
            return;
        }
        Schema::create('settlement_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('platform_fee_percentage', 5, 2)->default(0);
            $table->string('payout_frequency', 50)->default('monthly');
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

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
        if (Schema::hasTable('payout_calculations')) {
            return;
        }
        Schema::create('payout_calculations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payout_id');
            $table->foreign('payout_id')->references('id')->on('payouts')->onDelete('cascade');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->decimal('total_revenue', 12, 2);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('organizer_share', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->json('breakdown')->nullable();
            $table->timestamps();

            $table->unique('payout_id');
            $table->index(['event_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payout_calculations');
    }
};

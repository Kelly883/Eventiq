<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Disable the implicit transaction wrapper for this migration.
    // On managed PostgreSQL (Render, RDS, etc.), a prior statement failure
    // in the same transaction aborts the entire transaction, causing
    // subsequent CREATE TABLE/ALTER TABLE to fail with "current transaction is aborted".
    // By opting out, each statement runs in its own autocommit transaction.
    public $withinTransaction = false;
    public function up(): void
    {
        if (Schema::hasTable('payout_calculations')) {
            return;
        }

        $isPostgres = Schema::getConnection()->getDriverName() === 'pgsql';
        Schema::create('payout_calculations', function (Blueprint $table) use ($isPostgres) {
            if ($isPostgres) {
                $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('payout_id');
            $table->foreignId('organizer_id');
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

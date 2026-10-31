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
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tickets')) {
            return;
        }

        $isPostgres = Schema::getConnection()->getDriverName() === 'pgsql';
        Schema::create('tickets', function (Blueprint $table) use ($isPostgres) {
            if ($isPostgres) {
                $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('order_id');
            $table->uuid('user_id');
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_tier_id')->constrained()->onDelete('cascade');
            
            // QR code specific fields from Step 70
            $table->text('qr_code_data')->nullable();
            $table->string('qr_code_secret')->nullable();
            $table->timestamp('qr_code_generated_at')->nullable();
            $table->timestamp('qr_code_expires_at')->nullable();
            
            // Status and check-in fields
            $table->enum('status', ['valid', 'checked_in', 'void'])->default('valid');
            $table->timestamp('checked_in_at')->nullable();
            $table->uuid('checked_in_by')->nullable();
            $table->integer('qr_code_scanned_count')->default(0);
            $table->timestamp('last_qr_scan_at')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('checked_in_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for fast lookups
            $table->index(['event_id', 'status'], 'idx_tickets_event_status');
            $table->index(['event_id', 'checked_in_at'], 'idx_tickets_event_checkin');
            $table->index('qr_code_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
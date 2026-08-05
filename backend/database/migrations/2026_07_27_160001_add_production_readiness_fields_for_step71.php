<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds production-readiness fields for Step 71:
     * - seat_number, section to tickets for venue management
     * - device_id to fraud_events for forensics
     * - sync_status to tickets for offline check-ins
     * - check constraints for inventory integrity
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Add seat/section fields for venue management
            if (!Schema::hasColumn('tickets', 'seat_number')) {
                $table->string('seat_number')->nullable()->after('tier')
                      ->comment('Seat number for assigned seating events');
            }

            if (!Schema::hasColumn('tickets', 'section')) {
                $table->string('section')->nullable()->after('seat_number')
                      ->comment('Venue section for grouped seating');
            }

            // Add sync_status for offline check-in scenarios
            if (!Schema::hasColumn('tickets', 'sync_status')) {
                $table->enum('sync_status', ['synced', 'pending', 'failed'])->default('synced')->after('checked_in_by')
                      ->comment('Sync status for offline check-in queue');
            }

            // Add index for offline sync queries
            try {
                if (!Schema::hasIndex('tickets', 'idx_tickets_sync_status')) {
                    $table->index('sync_status', 'idx_tickets_sync_status');
                }
            } catch (\Exception $e) {
                // Index may already exist
            }
        });

        Schema::table('fraud_events', function (Blueprint $table) {
            // Add device_id for forensics
            if (!Schema::hasColumn('fraud_events', 'device_id')) {
                $table->string('device_id')->nullable()->after('second_check_in_by')
                      ->comment('Device/scanner ID that detected the fraud');
            }

            // Add index for device-based queries
            try {
                if (!Schema::hasIndex('fraud_events', 'idx_fraud_device_id')) {
                    $table->index('device_id', 'idx_fraud_device_id');
                }
            } catch (\Exception $e) {
                // Index may already exist
            }
        });

        Schema::table('ticket_inventory', function (Blueprint $table) {
            // Add check constraint for data integrity (MySQL/PostgreSQL only)
            // SQLite doesn't support CHECK constraints in the same way
            try {
                \DB::statement('ALTER TABLE ticket_inventory ADD CONSTRAINT chk_inventory_limits CHECK (total_checked_in <= total_available)');
                \DB::statement('ALTER TABLE ticket_inventory ADD CONSTRAINT chk_void_limits CHECK (total_void <= total_available)');
            } catch (\Exception $e) {
                // Constraints may already exist or database doesn't support them
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Drop seat_number, section, sync_status columns
            try {
                $table->dropIndex('idx_tickets_sync_status');
            } catch (\Exception $e) {
                // Index may not exist
            }

            if (Schema::hasColumn('tickets', 'sync_status')) {
                $table->dropColumn('sync_status');
            }

            if (Schema::hasColumn('tickets', 'section')) {
                $table->dropColumn('section');
            }

            if (Schema::hasColumn('tickets', 'seat_number')) {
                $table->dropColumn('seat_number');
            }
        });

        Schema::table('fraud_events', function (Blueprint $table) {
            // Drop device_id column
            try {
                $table->dropIndex('idx_fraud_device_id');
            } catch (\Exception $e) {
                // Index may not exist
            }

            if (Schema::hasColumn('fraud_events', 'device_id')) {
                $table->dropColumn('device_id');
            }
        });

        Schema::table('ticket_inventory', function (Blueprint $table) {
            // Drop check constraints
            try {
                \DB::statement('ALTER TABLE ticket_inventory DROP CONSTRAINT chk_inventory_limits');
            } catch (\Exception $e) {
                // Constraint may not exist
            }

            try {
                \DB::statement('ALTER TABLE ticket_inventory DROP CONSTRAINT chk_void_limits');
            } catch (\Exception $e) {
                // Constraint may not exist
            }
        });
    }
};
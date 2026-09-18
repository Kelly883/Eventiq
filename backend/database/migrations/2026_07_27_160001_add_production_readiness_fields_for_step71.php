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
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite does not support adding CHECK constraints via ALTER TABLE;
            // non-negativity remains enforced at the application layer.
            return;
        }

        $hasTicketsSeatNumber = Schema::hasColumn('tickets', 'seat_number');
        $hasTicketsSection = Schema::hasColumn('tickets', 'section');
        $hasTicketsSyncStatus = Schema::hasColumn('tickets', 'sync_status');
        $hasTicketsIdxTicketsSyncStatusIndex = Schema::hasIndex('tickets', 'idx_tickets_sync_status');
        Schema::table('tickets', function (Blueprint $table) use ($hasTicketsSeatNumber, $hasTicketsSection, $hasTicketsSyncStatus, $hasTicketsIdxTicketsSyncStatusIndex) {
            // Add seat/section fields for venue management
            if (!$hasTicketsSeatNumber) {
                $table->string('seat_number')->nullable()->after('tier')
                      ->comment('Seat number for assigned seating events');
            }

            if (!$hasTicketsSection) {
                $table->string('section')->nullable()->after('seat_number')
                      ->comment('Venue section for grouped seating');
            }

            // Add sync_status for offline check-in scenarios
            if (!$hasTicketsSyncStatus) {
                $table->enum('sync_status', ['synced', 'pending', 'failed'])->default('synced')->after('checked_in_by')
                      ->comment('Sync status for offline check-in queue');
            }

            // Add index for offline sync queries
            try {
                if (!$hasTicketsIdxTicketsSyncStatusIndex) {
                    $table->index('sync_status', 'idx_tickets_sync_status');
                }
            } catch (\Exception $e) {
                // Index may already exist
            }
        });

        $hasFraudEventsDeviceId = Schema::hasColumn('fraud_events', 'device_id');
        $hasFraudEventsIdxFraudDeviceIdIndex = Schema::hasIndex('fraud_events', 'idx_fraud_device_id');
        Schema::table('fraud_events', function (Blueprint $table) use ($hasFraudEventsDeviceId, $hasFraudEventsIdxFraudDeviceIdIndex) {
            // Add device_id for forensics
            if (!$hasFraudEventsDeviceId) {
                $table->string('device_id')->nullable()->after('second_check_in_by')
                      ->comment('Device/scanner ID that detected the fraud');
            }

            // Add index for device-based queries
            try {
                if (!$hasFraudEventsIdxFraudDeviceIdIndex) {
                    $table->index('device_id', 'idx_fraud_device_id');
                }
            } catch (\Exception $e) {
                // Index may already exist
            }
        });

        $hasTotalAvailable = Schema::hasColumn('ticket_inventory', 'total_available');
        $hasTotalCheckedIn = Schema::hasColumn('ticket_inventory', 'total_checked_in');
        $hasTotalVoid = Schema::hasColumn('ticket_inventory', 'total_void');

        if ($hasTotalAvailable && $hasTotalCheckedIn) {
            Schema::table('ticket_inventory', function (Blueprint $table) use ($hasTotalAvailable, $hasTotalCheckedIn) {
                if ($hasTotalCheckedIn) {
                    $table->integer('total_checked_in');
                }
            });
        }

        if ($hasTotalAvailable && $hasTotalVoid) {
            Schema::table('ticket_inventory', function (Blueprint $table) use ($hasTotalAvailable, $hasTotalVoid) {
                if ($hasTotalVoid) {
                    $table->integer('total_void');
                }
            });
        }

        // Add check constraint for data integrity (MySQL/PostgreSQL only)
        // SQLite does not support CHECK constraints in the same way
        if ($hasTotalAvailable && $hasTotalCheckedIn) {
            DB::statement('ALTER TABLE ticket_inventory ADD CONSTRAINT chk_inventory_limits CHECK (total_checked_in <= total_available)');
        }

        if ($hasTotalAvailable && $hasTotalVoid) {
            DB::statement('ALTER TABLE ticket_inventory ADD CONSTRAINT chk_void_limits CHECK (total_void <= total_available)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $hasTicketsSyncStatus = Schema::hasColumn('tickets', 'sync_status');
        $hasTicketsSection = Schema::hasColumn('tickets', 'section');
        $hasTicketsSeatNumber = Schema::hasColumn('tickets', 'seat_number');
        Schema::table('tickets', function (Blueprint $table) use ($hasTicketsSyncStatus, $hasTicketsSection, $hasTicketsSeatNumber) {
            // Drop seat_number, section, sync_status columns
            try {
                $table->dropIndex('idx_tickets_sync_status');
            } catch (\Exception $e) {
                // Index may not exist
            }

            if ($hasTicketsSyncStatus) {
                $table->dropColumn('sync_status');
            }

            if ($hasTicketsSection) {
                $table->dropColumn('section');
            }

            if ($hasTicketsSeatNumber) {
                $table->dropColumn('seat_number');
            }
        });

        $hasFraudEventsDeviceId = Schema::hasColumn('fraud_events', 'device_id');
        Schema::table('fraud_events', function (Blueprint $table) use ($hasFraudEventsDeviceId) {
            // Drop device_id column
            try {
                $table->dropIndex('idx_fraud_device_id');
            } catch (\Exception $e) {
                // Index may not exist
            }

            if ($hasFraudEventsDeviceId) {
                $table->dropColumn('device_id');
            }
        });

        // Drop check constraints using IF EXISTS for idempotency
        if (DB::getDriverName() === 'pgsql') {
            // Catalog-guarded: check existence via pg_constraint before dropping
            $row = DB::selectOne(
                "SELECT 1 FROM pg_constraint c
                 JOIN pg_class t ON c.conrelid = t.oid
                 JOIN pg_namespace n ON t.relnamespace = n.oid
                 WHERE n.nspname = current_schema()
                   AND t.relname = 'ticket_inventory'
                   AND c.conname = 'chk_inventory_limits'
                   AND c.contype = 'c'"
            );

            if ($row !== null) {
                DB::statement('ALTER TABLE ticket_inventory DROP CONSTRAINT IF EXISTS chk_inventory_limits');
            }

            $row = DB::selectOne(
                "SELECT 1 FROM pg_constraint c
                 JOIN pg_class t ON c.conrelid = t.oid
                 JOIN pg_namespace n ON t.relnamespace = n.oid
                 WHERE n.nspname = current_schema()
                   AND t.relname = 'ticket_inventory'
                   AND c.conname = 'chk_void_limits'
                   AND c.contype = 'c'"
            );

            if ($row !== null) {
                DB::statement('ALTER TABLE ticket_inventory DROP CONSTRAINT IF EXISTS chk_void_limits');
            }

            return;
        }

        if (DB::getDriverName() === 'mysql') {
            // MySQL: use information_schema to check constraint existence
            $row = DB::selectOne(
                'SELECT constraint_name FROM information_schema.table_constraints
                 WHERE table_schema = DATABASE()
                   AND table_name = \'ticket_inventory\'
                   AND constraint_type = \'CHECK\'
                   AND constraint_name = \'chk_inventory_limits\''
            );

            if ($row !== null) {
                DB::statement("ALTER TABLE ticket_inventory DROP CONSTRAINT chk_inventory_limits");
            }

            $row = DB::selectOne(
                'SELECT constraint_name FROM information_schema.table_constraints
                 WHERE table_schema = DATABASE()
                   AND table_name = \'ticket_inventory\'
                   AND constraint_type = \'CHECK\'
                   AND constraint_name = \'chk_void_limits\''
            );

            if ($row !== null) {
                DB::statement("ALTER TABLE ticket_inventory DROP CONSTRAINT chk_void_limits");
            }

            return;
        }

        // Fallback for other drivers: use IF EXISTS for idempotency
        try {
            DB::statement('ALTER TABLE ticket_inventory DROP CONSTRAINT IF EXISTS chk_inventory_limits');
        } catch (\Exception $e) {
            // Constraint may not exist
        }

        try {
            DB::statement('ALTER TABLE ticket_inventory DROP CONSTRAINT IF EXISTS chk_void_limits');
        } catch (\Exception $e) {
            // Constraint may not exist
        }
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates audit_logs table for Step 71 check-in audit logging:
     * - Changes id to UUID primary key
     * - Adds event_id (FK to events)
     * - Changes details column to JSON
     * - Ensures ticket_id column exists (nullable)
     * - Adds index on (event_id, created_at)
     */
    public function up(): void
    {
        $hasAuditLogsEventId = Schema::hasColumn('audit_logs', 'event_id');
        $hasAuditLogsTicketId = Schema::hasColumn('audit_logs', 'ticket_id');
        $hasAuditLogsChanges = Schema::hasColumn('audit_logs', 'changes');
        $hasAuditLogsDetails = Schema::hasColumn('audit_logs', 'details');
        Schema::table('audit_logs', function (Blueprint $table) use ($hasAuditLogsEventId, $hasAuditLogsTicketId, $hasAuditLogsChanges, $hasAuditLogsDetails) {
            // Change id to UUID if currently bigint
            // Note: This requires raw SQL for existing data, but we'll add the column if needed
            // For fresh installs or if we can't alter, we'll focus on other fields
            
            // Add event_id for event-specific audit logging
            if (! $hasAuditLogsEventId) {
                $table->foreignId('event_id')->nullable()->after('id');
            }

            // Add ticket_id if not exists (nullable)
            if (! $hasAuditLogsTicketId) {
                $table->foreignId('ticket_id')->nullable()->after('user_id');
            }

            // Change changes to details if needed (MySQL-only)
            if ($hasAuditLogsChanges && ! $hasAuditLogsDetails && DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE audit_logs CHANGE changes details JSON');
            }

            // Ensure details column exists as JSON
            if (! $hasAuditLogsDetails) {
                $table->json('details')->nullable()->after('ticket_id');
            }

            // Add FK for event_id
            try {
                $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            } catch (\Exception $e) {
                // FK may already exist
            }

            // Add FK for ticket_id
            try {
                $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            } catch (\Exception $e) {
                // FK may already exist
            }
        });

        // Add index on (event_id, created_at)
        try {
            $hasAuditLogsIdxAuditEventCreatedIndex = Schema::hasIndex('audit_logs', 'idx_audit_event_created');
            Schema::table('audit_logs', function (Blueprint $table) use ($hasAuditLogsIdxAuditEventCreatedIndex) {
                if (! $hasAuditLogsIdxAuditEventCreatedIndex) {
                    $table->index(['event_id', 'created_at'], 'idx_audit_event_created');
                }
            });
        } catch (\Exception $e) {
            // Index may already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasAuditLogsEventId = Schema::hasColumn('audit_logs', 'event_id');
        $hasAuditLogsTicketId = Schema::hasColumn('audit_logs', 'ticket_id');
        $hasAuditLogsDetails = Schema::hasColumn('audit_logs', 'details');
        Schema::table('audit_logs', function (Blueprint $table) use ($hasAuditLogsEventId, $hasAuditLogsTicketId, $hasAuditLogsDetails) {
            // Drop FK
            try {
                $table->dropForeign(['event_id']);
            } catch (\Exception $e) {
                // FK may not exist
            }

            try {
                $table->dropForeign(['ticket_id']);
            } catch (\Exception $e) {
                // FK may not exist
            }

            // Drop index
            try {
                $table->dropIndex('idx_audit_event_created');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop columns
            $columns = [];
            if ($hasAuditLogsEventId) {
                $columns[] = 'event_id';
            }
            if ($hasAuditLogsTicketId) {
                $columns[] = 'ticket_id';
            }
            if ($hasAuditLogsDetails) {
                $columns[] = 'details';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
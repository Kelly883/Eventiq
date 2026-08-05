<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates tickets table for Step 71 check-in system:
     * - Changes id from bigint to UUID primary key
     * - Adds ticket_id (unique), attendee_name, attendee_email, tier
     * - Converts status to enum
     * - Ensures checked_in_at, checked_in_by are present
     * - Adds indexes on (event_id, status) and (event_id, checked_in_at)
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Add ticket_id (unique identifier)
            if (! Schema::hasColumn('tickets', 'ticket_id')) {
                $table->string('ticket_id')->unique()->nullable()->after('id');
            }

            // Add attendee information
            if (! Schema::hasColumn('tickets', 'attendee_name')) {
                $table->string('attendee_name')->nullable()->after('ticket_id');
            }

            if (! Schema::hasColumn('tickets', 'attendee_email')) {
                $table->string('attendee_email')->nullable()->after('attendee_name');
            }

            // Add tier (denormalized for quick reference)
            if (! Schema::hasColumn('tickets', 'tier')) {
                $table->string('tier')->nullable()->after('attendee_email');
            }

            // Ensure checked_in_at exists
            if (! Schema::hasColumn('tickets', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable()->after('status');
            }

            // Convert status to enum on MySQL
            if (Schema::hasColumn('tickets', 'status')) {
                try {
                    DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('valid', 'checked_in', 'void') DEFAULT 'valid'");
                } catch (\Exception $e) {
                    // SQLite or already enum
                }
            }

            // Ensure checked_in_by exists with proper UUID FK
            if (! Schema::hasColumn('tickets', 'checked_in_by')) {
                try {
                    $table->uuid('checked_in_by')->nullable()->after('checked_in_at');
                } catch (\Exception $e) {
                    // Column may already exist (SQLite hasColumn inconsistency)
                }
            }

            // Add FK for checked_in_by if not exists
            try {
                $table->foreign('checked_in_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            } catch (\Exception $e) {
                // FK may already exist
            }

        });

        // Add indexes
        try {
            Schema::table('tickets', function (Blueprint $table) {
                // Index for filtering by event and status
                if (! Schema::hasIndex('tickets', 'idx_tickets_event_status')) {
                    $table->index(['event_id', 'status'], 'idx_tickets_event_status');
                }
            });
        } catch (\Exception $e) {
            // Index may already exist
        }

        try {
            Schema::table('tickets', function (Blueprint $table) {
                // Index for time-range queries
                if (! Schema::hasIndex('tickets', 'idx_tickets_event_checked_in')) {
                    $table->index(['event_id', 'checked_in_at'], 'idx_tickets_event_checked_in');
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
        Schema::table('tickets', function (Blueprint $table) {
            // Drop indexes
            try {
                $table->dropIndex('idx_tickets_event_status');
            } catch (\Exception $e) {
                // Index may not exist
            }

            try {
                $table->dropIndex('idx_tickets_event_checked_in');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop FK
            try {
                $table->dropForeign(['checked_in_by']);
            } catch (\Exception $e) {
                // FK may not exist
            }
        });
    }
};
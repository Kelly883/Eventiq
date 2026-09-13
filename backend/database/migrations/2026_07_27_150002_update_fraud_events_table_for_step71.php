<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates fraud_events table for Step 71 check-in system:
     * - Renames event_type to fraud_type with new enum values
     * - Ensures all check-in specific fields exist
     * - Adds proper indexes for check-in fraud queries
     */
    public function up(): void
    {
        $hasFraudEventsEventType = Schema::hasColumn('fraud_events', 'event_type');
        $hasFraudEventsFraudType = Schema::hasColumn('fraud_events', 'fraud_type');
        $hasFraudEventsTicketId = Schema::hasColumn('fraud_events', 'ticket_id');
        $hasFraudEventsFirstCheckInAt = Schema::hasColumn('fraud_events', 'first_check_in_at');
        $hasFraudEventsFirstCheckInBy = Schema::hasColumn('fraud_events', 'first_check_in_by');
        $hasFraudEventsSecondCheckInAt = Schema::hasColumn('fraud_events', 'second_check_in_at');
        $hasFraudEventsSecondCheckInBy = Schema::hasColumn('fraud_events', 'second_check_in_by');
        $hasFraudEventsRiskLevel = Schema::hasColumn('fraud_events', 'risk_level');
        $hasFraudEventsNotes = Schema::hasColumn('fraud_events', 'notes');
        Schema::table('fraud_events', function (Blueprint $table) use ($hasFraudEventsEventType, $hasFraudEventsFraudType, $hasFraudEventsTicketId, $hasFraudEventsFirstCheckInAt, $hasFraudEventsFirstCheckInBy, $hasFraudEventsSecondCheckInAt, $hasFraudEventsSecondCheckInBy, $hasFraudEventsRiskLevel, $hasFraudEventsNotes) {
            // Rename event_type to fraud_type if needed
            if ($hasFraudEventsEventType && !$hasFraudEventsFraudType) {
                $table->renameColumn('event_type', 'fraud_type');
            }
            
            // Alter fraud_type enum to include only check-in related values
            // Note: This requires raw SQL, we'll try to modify if column exists
            if ($hasFraudEventsFraudType) {
                try {
                    \DB::statement("ALTER TABLE fraud_events MODIFY COLUMN fraud_type ENUM('duplicate_checkin', 'invalid_qr', 'manual_override')");
                } catch (\Exception $e) {
                    // Column may not exist or SQLite doesn't support this
                }
            }

            // Ensure ticket_id exists with UUID FK
            if ($hasFraudEventsTicketId) {
                try {
                    $table->foreign('ticket_id')
                        ->references('id')
                        ->on('tickets')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // FK may already exist
                }
            }

            // Ensure all check-in specific fields exist
            if (!$hasFraudEventsFirstCheckInAt) {
                $table->timestamp('first_check_in_at')->nullable()->after('detected_at');
            }
            
            if (!$hasFraudEventsFirstCheckInBy) {
                $table->uuid('first_check_in_by')->nullable()->after('first_check_in_at');
            }
            
            if (!$hasFraudEventsSecondCheckInAt) {
                $table->timestamp('second_check_in_at')->nullable()->after('first_check_in_by');
            }
            
            if (!$hasFraudEventsSecondCheckInBy) {
                $table->uuid('second_check_in_by')->nullable()->after('second_check_in_at');
            }

            // Ensure FKs for check-in users exist
            if ($hasFraudEventsFirstCheckInBy) {
                try {
                    $table->foreign('first_check_in_by')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // FK may already exist
                }
            }
            
            if ($hasFraudEventsSecondCheckInBy) {
                try {
                    $table->foreign('second_check_in_by')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // FK may already exist
                }
            }

            // Ensure risk_level exists with correct enum values
            if ($hasFraudEventsRiskLevel) {
                try {
                    \DB::statement("ALTER TABLE fraud_events MODIFY COLUMN risk_level ENUM('low', 'medium', 'high') DEFAULT 'medium'");
                } catch (\Exception $e) {
                    // Column may already be correct or SQLite doesn't support this
                }
            }

            // Ensure notes exists
            if (!$hasFraudEventsNotes) {
                $table->text('notes')->nullable()->after('risk_level');
            }
        });

        // Add indexes for check-in fraud queries
        try {
            $hasFraudEventsIdxFraudTicketEventIndex = Schema::hasIndex('fraud_events', 'idx_fraud_ticket_event');
            Schema::table('fraud_events', function (Blueprint $table) use ($hasFraudEventsIdxFraudTicketEventIndex) {
                if (!$hasFraudEventsIdxFraudTicketEventIndex) {
                    $table->index(['ticket_id', 'event_id'], 'idx_fraud_ticket_event');
                }
            });
        } catch (\Exception $e) {
            // Index may already exist
        }

        try {
            $hasFraudEventsIdxFraudEventDetectedIndex = Schema::hasIndex('fraud_events', 'idx_fraud_event_detected');
            Schema::table('fraud_events', function (Blueprint $table) use ($hasFraudEventsIdxFraudEventDetectedIndex) {
                if (!$hasFraudEventsIdxFraudEventDetectedIndex) {
                    $table->index(['event_id', 'detected_at'], 'idx_fraud_event_detected');
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
        Schema::table('fraud_events', function (Blueprint $table) {
            // Drop indexes
            try {
                $table->dropIndex('idx_fraud_ticket_event');
            } catch (\Exception $e) {
                // Index may not exist
            }

            try {
                $table->dropIndex('idx_fraud_event_detected');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop FKs
            try {
                $table->dropForeign(['first_check_in_by']);
            } catch (\Exception $e) {
                // FK may not exist
            }

            try {
                $table->dropForeign(['second_check_in_by']);
            } catch (\Exception $e) {
                // FK may not exist
            }

            try {
                $table->dropForeign(['ticket_id']);
            } catch (\Exception $e) {
                // FK may not exist
            }
        });
    }
};
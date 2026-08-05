<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove soft deletes from fraud_events table.
     *
     * Fraud audit trails must be IMMUTABLE - records should never be
     * soft-deleted. If a record needs to be hidden, transition status
     * to 'archived' instead.
     *
     * Handles both MySQL (native DROP COLUMN) and SQLite (table rebuild).
     */
    public function up(): void
    {
        if (!Schema::hasTable('fraud_events') || !Schema::hasColumn('fraud_events', 'deleted_at')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite does not support DROP COLUMN natively in older versions.
            // We rebuild the table without the deleted_at column.
            $this->rebuildTableWithoutSoftDeletes();
        } else {
            // MySQL, PostgreSQL, etc. - native drop column
            Schema::table('fraud_events', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    /**
     * Reverse the migration - restore soft deletes.
     */
    public function down(): void
    {
        if (!Schema::hasTable('fraud_events') || Schema::hasColumn('fraud_events', 'deleted_at')) {
            return;
        }

        Schema::table('fraud_events', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Rebuild the fraud_events table without the deleted_at column for SQLite.
     *
     * Process:
     * 1. Create a temp table with the new schema (no deleted_at)
     * 2. Copy all data from the original table
     * 3. Drop the original table
     * 4. Rename temp table to original name
     * 5. Recreate all indexes and foreign keys
     */
    private function rebuildTableWithoutSoftDeletes(): void
    {
        // 1. Create temp table with the exact schema minus deleted_at
        DB::statement(<<<'SQL'
            CREATE TABLE fraud_events_new (
                id                  CHAR(36) PRIMARY KEY NOT NULL,
                order_id            CHAR(36) NOT NULL,
                user_id             CHAR(36) NOT NULL,
                ticket_id           CHAR(36),
                event_id            INTEGER,
                event_type          TEXT NOT NULL CHECK (event_type IN (
                    'duplicate_ticket_attempt',
                    'velocity_check_failed',
                    'payment_pattern_suspicious',
                    'device_fingerprint_mismatch',
                    'geolocation_anomaly',
                    'card_testing',
                    'high_risk_payment_method',
                    'duplicate_checkin',
                    'invalid_qr',
                    'manual_override'
                )),
                risk_score          DECIMAL(5,2) NOT NULL,
                risk_level          TEXT NOT NULL CHECK (risk_level IN ('low', 'medium', 'high')),
                detection_method    TEXT NOT NULL CHECK (detection_method IN (
                    'sift_science',
                    'stripe_radar',
                    'duplicate_detection',
                    'velocity_check',
                    'rule_based',
                    'qr_validation',
                    'manual_review'
                )),
                fraud_factors       TEXT CHECK (fraud_factors IS NULL OR json_valid(fraud_factors)),
                payment_details     TEXT CHECK (payment_details IS NULL OR json_valid(payment_details)),
                velocity_metrics    TEXT CHECK (velocity_metrics IS NULL OR json_valid(velocity_metrics)),
                device_info         TEXT CHECK (device_info IS NULL OR json_valid(device_info)),
                duplicate_ticket_info TEXT CHECK (duplicate_ticket_info IS NULL OR json_valid(duplicate_ticket_info)),
                detected_at         DATETIME,
                first_check_in_at   DATETIME,
                first_check_in_by   CHAR(36),
                second_check_in_at  DATETIME,
                second_check_in_by  CHAR(36),
                status              TEXT NOT NULL DEFAULT 'flagged' CHECK (status IN (
                    'flagged',
                    'reviewed',
                    'approved',
                    'rejected',
                    'auto_blocked'
                )),
                reviewed_by         CHAR(36),
                review_notes        TEXT,
                reviewed_at         DATETIME,
                notes               TEXT,
                created_at          DATETIME,
                updated_at          DATETIME,
                session_id          VARCHAR(255),
                ip_address          VARCHAR(45),
                card_fingerprint    VARCHAR(64),
                amount              DECIMAL(10,2),
                currency            VARCHAR(3),
                gateway_response_code VARCHAR(10),
                automated_action_taken VARCHAR(50),
                source              VARCHAR(50),
                FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY(ticket_id) REFERENCES tickets(id) ON DELETE SET NULL,
                FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE SET NULL,
                FOREIGN KEY(first_check_in_by) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY(second_check_in_by) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL
            )
        SQL);

        // 2. Copy all data from original table
        DB::statement('
            INSERT INTO fraud_events_new (
                id, order_id, user_id, ticket_id, event_id,
                event_type, risk_score, risk_level, detection_method,
                fraud_factors, payment_details, velocity_metrics, device_info,
                duplicate_ticket_info,
                detected_at, first_check_in_at, first_check_in_by,
                second_check_in_at, second_check_in_by,
                status, reviewed_by, review_notes, reviewed_at, notes,
                created_at, updated_at,
                session_id, ip_address, card_fingerprint, amount, currency,
                gateway_response_code, automated_action_taken, source
            )
            SELECT
                id, order_id, user_id, ticket_id, event_id,
                event_type, risk_score, risk_level, detection_method,
                fraud_factors, payment_details, velocity_metrics, device_info,
                duplicate_ticket_info,
                detected_at, first_check_in_at, first_check_in_by,
                second_check_in_at, second_check_in_by,
                status, reviewed_by, review_notes, reviewed_at, notes,
                created_at, updated_at,
                session_id, ip_address, card_fingerprint, amount, currency,
                gateway_response_code, automated_action_taken, source
            FROM fraud_events
        ');

        // 3. Drop the original table
        Schema::drop('fraud_events');

        // 4. Rename temp table to original name
        DB::statement('ALTER TABLE fraud_events_new RENAME TO fraud_events');

        // 5. Recreate all indexes and foreign keys
        DB::statement('CREATE INDEX idx_fraud_user_created ON fraud_events(user_id, created_at)');
        DB::statement('CREATE INDEX idx_fraud_status_created ON fraud_events(status, created_at)');
        DB::statement('CREATE INDEX idx_fraud_risk_created ON fraud_events(risk_level, created_at)');
        DB::statement('CREATE INDEX idx_fraud_ticket_event ON fraud_events(ticket_id, event_id)');
        DB::statement('CREATE INDEX idx_fraud_event_detected ON fraud_events(event_id, detected_at)');
        DB::statement('CREATE INDEX idx_fraud_type_created ON fraud_events(event_type, created_at)');
        DB::statement('CREATE UNIQUE INDEX idx_fraud_order_type_unique ON fraud_events(order_id, event_type)');
        DB::statement('CREATE INDEX fraud_events_order_id_index ON fraud_events(order_id)');
        DB::statement('CREATE INDEX fraud_events_detection_method_index ON fraud_events(detection_method)');
    }
};

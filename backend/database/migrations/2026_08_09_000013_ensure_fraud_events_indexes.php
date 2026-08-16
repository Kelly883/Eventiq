<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 71 — Final check-in index reconciliation for fraud_events.
 *
 * The fraud_events table is rebuilt by several "fix" migrations
 * (2026_08_09_000003 / 000008 / 000011). Each table rebuild on
 * SQLite creates a brand-new table and copies data, which drops every
 * custom index that was defined on the original table. The index-adding
 * migration 2026_08_09_000010 runs *before* the final rebuild
 * (2026_08_09_000011), so its indexes are silently lost.
 *
 * This migration runs *after* the last rebuild and re-creates every
 * index the check-in / fraud feature relies on. All statements are
 * guarded by `CREATE INDEX IF NOT EXISTS` equivalents
 * (Schema::hasIndex + try/catch) so they are idempotent and safe to
 * run on both SQLite (testing) and MySQL (production).
 */
return new class extends Migration
{
    private array $indexes = [
        // Dashboard: fraud activity per user over time
        'idx_fraud_user_created'      => ['fraud_events', ['user_id', 'created_at']],
        // Dashboard: filter by status + recency
        'idx_fraud_status_created'    => ['fraud_events', ['status', 'created_at']],
        // Dashboard: filter by risk level + recency
        'idx_fraud_risk_created'      => ['fraud_events', ['risk_level', 'created_at']],
        // Check-in fraud: locate a duplicate/duplicate-checkin by ticket + event
        'idx_fraud_ticket_event'      => ['fraud_events', ['ticket_id', 'event_id']],
        // Check-in fraud: scan fraud by event within a detection window
        'idx_fraud_event_detected'    => ['fraud_events', ['event_id', 'detected_at']],
        // Review workflow: open items per reviewer
        'idx_fraud_reviewer_status'   => ['fraud_events', ['reviewed_by', 'status']],
        // Global recency scan
        'idx_fraud_created_at'        => ['fraud_events', ['created_at']],
        // Point lookups
        'idx_fraud_ip_address'        => ['fraud_events', ['ip_address']],
        'idx_fraud_card_fingerprint'  => ['fraud_events', ['card_fingerprint']],
        'idx_fraud_user_email'        => ['fraud_events', ['user_email']],
        'idx_fraud_detection_method'  => ['fraud_events', ['detection_method']],
        // Single-column covering lookups
        'idx_fraud_order_id'          => ['fraud_events', ['order_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $name => $spec) {
            [$table, $columns] = $spec;

            if (! Schema::hasTable($table)) {
                continue;
            }

            // Skip columns that do not exist on this table (defensive: some
            // columns are added by later migrations and may be absent on
            // older snapshots).
            if (! $this->allColumnsExist($table, $columns)) {
                continue;
            }

            if (Schema::hasIndex($table, $name)) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $table) use ($name, $columns) {
                    $table->index($columns, $name);
                });
            } catch (\Throwable $e) {
                // Index may already exist or column unavailable on this driver.
            }
        }

        // Ensure the order-id + fraud-type uniqueness constraint. On a fresh
        // database the column may be empty for unrelated orders, so we only
        // enforce uniqueness where a fraud_type is actually set.
        if (Schema::hasTable('fraud_events')
            && Schema::hasColumn('fraud_events', 'order_id')
            && Schema::hasColumn('fraud_events', 'fraud_type')
            && ! Schema::hasIndex('fraud_events', 'idx_fraud_order_type_unique')) {
            try {
                DB::statement(
                    'CREATE UNIQUE INDEX IF NOT EXISTS idx_fraud_order_type_unique ON fraud_events (order_id, fraud_type)'
                );
            } catch (\Throwable $e) {
                // Already exists or unsupported.
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->indexes) as $name) {
            try {
                DB::statement('DROP INDEX IF EXISTS ' . $name);
            } catch (\Throwable $e) {
            }
        }

        try {
            DB::statement('DROP INDEX IF EXISTS idx_fraud_order_type_unique');
        } catch (\Throwable $e) {
        }
    }

    private function allColumnsExist(string $table, array $columns): bool
    {
        $listing = Schema::getColumnListing($table);

        foreach ($columns as $column) {
            if (! in_array($column, $listing, true)) {
                return false;
            }
        }

        return true;
    }
};

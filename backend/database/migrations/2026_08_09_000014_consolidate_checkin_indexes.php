<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 71 — Consolidate check-in indexes.
 *
 * Follows up on the verification pass which found:
 *  - fraud_events ended up with no custom indexes because repeated
 *    "rebuild table" migrations wiped them; 2026_08_09_000013 restored
 *    them. This migration further trims redundancies and adds the few
 *    indexes the real check-in query patterns were missing.
 *  - Redundant / duplicate indexes across ticket_inventory,
 *    analytics_events_metrics and fraud_events (same leading column
 *    covered twice) that hurt write throughput on the check-in hot path.
 *
 * All operations are guarded by Schema::hasIndex and wrapped in
 * try/catch so they are idempotent and safe on both SQLite (tests) and
 * MySQL (production).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->dropIndexIfExists('fraud_events', 'idx_fraud_order_id');
        // idx_fraud_order_type_unique (order_id, fraud_type) already covers
        // order_id equality lookups, so the single-column idx_fraud_order_id
        // is redundant.

        $this->dropIndexIfExists('ticket_inventory', 'inv_event_id_idx');
        // Duplicate of idx_ticket_inventory_event and subsumed by the
        // inv_event_tier_idx composite; one event_id index is enough.

        $this->dropIndexIfExists('analytics_events_metrics', 'analytics_events_metrics_event_id_index');
        // Exact duplicate of idx_analytics_event.
        $this->dropIndexIfExists('analytics_events_metrics', 'analytics_events_metrics_organizer_id_index');
        // Subsumed by idx_metrics_organizer_event (organizer_id, event_id).

        $this->addIndex('fraud_events', ['fraud_type'], 'idx_fraud_type');

        $this->addIndex('check_ins', ['event_id', 'status'], 'idx_checkins_event_status');
        $this->addIndex('check_ins', ['scanned_by', 'checked_in_at'], 'idx_checkins_scanned_by_at');

        $this->normalizeTicketsTicketIdIndex();
    }

    public function down(): void
    {
        $this->dropIndexIfExists('fraud_events', 'idx_fraud_type');
        $this->dropIndexIfExists('check_ins', 'idx_checkins_event_status');
        $this->dropIndexIfExists('check_ins', 'idx_checkins_scanned_by_at');

        // Restore the single-column fraud_events order_id index we removed.
        $this->addIndex('fraud_events', ['order_id'], 'idx_fraud_order_id');
    }

    /**
     * The index idx_tickets_ticket_id_unique is named "..._unique" but is
     * not actually unique — misleading for a column that is the external
     * ticket identifier and must be unique for check-in dedup. Promote it
     * to a real UNIQUE index when the data allows it; otherwise fall back
     * to a correctly-named non-unique index so the migration never hard
     * fails on legacy duplicate ticket_id values.
     */
    private function normalizeTicketsTicketIdIndex(): void
    {
        if (! Schema::hasTable('tickets') || ! Schema::hasColumn('tickets', 'ticket_id')) {
            return;
        }

        if (Schema::hasIndex('tickets', 'idx_tickets_ticket_id_unique')) {
            try {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->dropIndex('idx_tickets_ticket_id_unique');
                });
            } catch (\Throwable $e) {
                return;
            }
        }

        // Try to make it a true UNIQUE index (guards against duplicates).
        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_tickets_ticket_id_unique ON tickets (ticket_id)');
        } catch (\Throwable $e) {
            // Duplicate non-null ticket_id values exist — keep a non-unique
            // index with an honest name instead.
            $this->addIndex('tickets', ['ticket_id'], 'idx_tickets_ticket_id');
            return;
        }
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($name) {
                $table->dropIndex($name);
            });
        } catch (\Throwable $e) {
            // Index name may not be dropable on this driver; ignore.
        }
    }

    private function addIndex(string $table, array $columns, string $name, bool $unique = false): void
    {
        if (! Schema::hasTable($table) || ! $this->columnsExist($table, $columns)) {
            return;
        }

        if (Schema::hasIndex($table, $name)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $name, $unique) {
                $unique
                    ? $table->unique($columns, $name)
                    : $table->index($columns, $name);
            });
        } catch (\Throwable $e) {
            // Already exists or unsupported; ignore.
        }
    }

    private function columnsExist(string $table, array $columns): bool
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

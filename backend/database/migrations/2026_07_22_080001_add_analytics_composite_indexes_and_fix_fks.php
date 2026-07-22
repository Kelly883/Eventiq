<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add composite indexes for analytics query performance and fix FK constraints
     * to prevent data loss on cascade deletes.
     *
     * Indexes added:
     *   - analytics_events_metrics: (organizer_id, event_id) for organizer dashboards
     *   - analytics_tier_performance: (event_id, ticket_tier_id) for per-event tier queries
     *   - analytics_sales_timeline: (event_id, ticket_tier_id, sale_timestamp) for tier time-series
     *
     * FK fixes (cascade → set null to preserve historical data):
     *   - analytics_sales_timeline.ticket_tier_id
     *   - analytics_tier_performance.ticket_tier_id
     *   - analytics_events_metrics.organizer_id
     */
    public function up(): void
    {
        // ===== PART 1: Add composite indexes =====

        // analytics_events_metrics: composite index for organizer dashboard queries
        Schema::table('analytics_events_metrics', function (Blueprint $table) {
            $table->index(['organizer_id', 'event_id'], 'idx_metrics_organizer_event');
        });

        // analytics_tier_performance: composite index for per-event tier queries
        Schema::table('analytics_tier_performance', function (Blueprint $table) {
            $table->index(['event_id', 'ticket_tier_id'], 'idx_tier_perf_event_tier');
        });

        // analytics_sales_timeline: full covering index for tier time-series queries
        Schema::table('analytics_sales_timeline', function (Blueprint $table) {
            $table->index(
                ['event_id', 'ticket_tier_id', 'sale_timestamp'],
                'idx_sales_timeline_full'
            );
        });

        // ===== PART 2: Fix FK constraints to prevent data loss =====

        // --- analytics_sales_timeline.ticket_tier_id: cascade → set null ---
        Schema::table('analytics_sales_timeline', function (Blueprint $table) {
            // Drop the existing FK constraint
            $table->dropForeign(['ticket_tier_id']);
        });
        Schema::table('analytics_sales_timeline', function (Blueprint $table) {
            // Re-add with set null on delete
            $table->foreign('ticket_tier_id')
                  ->references('id')
                  ->on('ticket_tiers')
                  ->onDelete('set null');
        });

        // --- analytics_tier_performance.ticket_tier_id: cascade → set null ---
        Schema::table('analytics_tier_performance', function (Blueprint $table) {
            $table->dropForeign(['ticket_tier_id']);
        });
        Schema::table('analytics_tier_performance', function (Blueprint $table) {
            $table->foreign('ticket_tier_id')
                  ->references('id')
                  ->on('ticket_tiers')
                  ->onDelete('set null');
        });

        // --- analytics_events_metrics.organizer_id: cascade → set null ---
        Schema::table('analytics_events_metrics', function (Blueprint $table) {
            $table->dropForeign(['organizer_id']);
        });
        Schema::table('analytics_events_metrics', function (Blueprint $table) {
            $table->foreign('organizer_id')
                  ->references('id')
                  ->on('organizers')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the changes.
     */
    public function down(): void
    {
        // Drop added composite indexes
        Schema::table('analytics_events_metrics', function (Blueprint $table) {
            $table->dropIndex('idx_metrics_organizer_event');
        });
        Schema::table('analytics_tier_performance', function (Blueprint $table) {
            $table->dropIndex('idx_tier_perf_event_tier');
        });
        Schema::table('analytics_sales_timeline', function (Blueprint $table) {
            $table->dropIndex('idx_sales_timeline_full');
        });

        // Restore original FK constraints (cascade)
        Schema::table('analytics_events_metrics', function (Blueprint $table) {
            $table->dropForeign(['organizer_id']);
        });
        Schema::table('analytics_events_metrics', function (Blueprint $table) {
            $table->foreign('organizer_id')
                  ->references('id')
                  ->on('organizers')
                  ->onDelete('cascade');
        });

        Schema::table('analytics_tier_performance', function (Blueprint $table) {
            $table->dropForeign(['ticket_tier_id']);
        });
        Schema::table('analytics_tier_performance', function (Blueprint $table) {
            $table->foreign('ticket_tier_id')
                  ->references('id')
                  ->on('ticket_tiers')
                  ->onDelete('cascade');
        });

        Schema::table('analytics_sales_timeline', function (Blueprint $table) {
            $table->dropForeign(['ticket_tier_id']);
        });
        Schema::table('analytics_sales_timeline', function (Blueprint $table) {
            $table->foreign('ticket_tier_id')
                  ->references('id')
                  ->on('ticket_tiers')
                  ->onDelete('cascade');
        });
    }
};


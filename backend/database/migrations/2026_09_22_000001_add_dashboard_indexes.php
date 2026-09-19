<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Add index on status for dashboard metrics filtering
            if (!Schema::hasIndex('events', 'events_status_index')) {
                $table->index('status', 'events_status_index');
            }
        });

        Schema::table('analytics_sales_timeline', function (Blueprint $table) {
            // Add composite index for activity feed queries
            if (!Schema::hasIndex('analytics_sales_timeline', 'idx_activity_feed')) {
                $table->index(['event_id', 'sale_timestamp'], 'idx_activity_feed');
            }
        });

        Schema::table('analytics_events_metrics', function (Blueprint $table) {
            // Ensure index on event_id for sum queries
            if (!Schema::hasIndex('analytics_events_metrics', 'idx_metrics_event')) {
                $table->index('event_id', 'idx_metrics_event');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasIndex('events', 'events_status_index')) {
                $table->dropIndex('events_status_index');
            }
        });

        Schema::table('analytics_sales_timeline', function (Blueprint $table) {
            if (Schema::hasIndex('analytics_sales_timeline', 'idx_activity_feed')) {
                $table->dropIndex('idx_activity_feed');
            }
        });

        Schema::table('analytics_events_metrics', function (Blueprint $table) {
            if (Schema::hasIndex('analytics_events_metrics', 'idx_metrics_event')) {
                $table->dropIndex('idx_metrics_event');
            }
        });
    }
};

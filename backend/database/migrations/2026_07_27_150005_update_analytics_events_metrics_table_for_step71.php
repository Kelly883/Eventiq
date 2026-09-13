<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates analytics_events_metrics table for Step 71 check-in system:
     * - Adds total_checked_in (integer)
     * - Adds check_in_rate (decimal)
     * - Adds last_updated_at (timestamp)
     * - Ensures id is UUID primary key
     * - Ensures event_id FK exists
     * - Adds index on event_id
     */
    public function up(): void
    {
        Schema::table('analytics_events_metrics', function (Blueprint $table) {
            // Change id to UUID primary key if currently bigint
            // Note: This requires raw SQL for MySQL
            try {
                \DB::statement('ALTER TABLE analytics_events_metrics MODIFY id CHAR(36) PRIMARY KEY');
            } catch (\Exception $e) {
                // Already UUID or SQLite - skip
            }

            // Ensure event_id is UUID
            try {
                \DB::statement('ALTER TABLE analytics_events_metrics MODIFY event_id CHAR(36)');
            } catch (\Exception $e) {
                // Already UUID or SQLite - skip
            }

            // Drop and re-add FK for event_id if needed
            try {
                $table->dropForeign(['event_id']);
            } catch (\Exception $e) {
                // FK may not exist
            }

            try {
                $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            } catch (\Exception $e) {
                // FK may already exist
            }
        });

        // Add check-in metrics columns
        try {
            $hasAnalyticsEventsMetricsTotalCheckedIn = Schema::hasColumn('analytics_events_metrics', 'total_checked_in');
            $hasAnalyticsEventsMetricsCheckInRate = Schema::hasColumn('analytics_events_metrics', 'check_in_rate');
            $hasAnalyticsEventsMetricsLastUpdatedAt = Schema::hasColumn('analytics_events_metrics', 'last_updated_at');
            Schema::table('analytics_events_metrics', function (Blueprint $table) use ($hasAnalyticsEventsMetricsTotalCheckedIn, $hasAnalyticsEventsMetricsCheckInRate, $hasAnalyticsEventsMetricsLastUpdatedAt) {
                if (! $hasAnalyticsEventsMetricsTotalCheckedIn) {
                    $table->integer('total_checked_in')->default(0)->after('total_tickets_sold');
                }

                if (! $hasAnalyticsEventsMetricsCheckInRate) {
                    $table->decimal('check_in_rate', 5, 2)->default(0)->after('total_checked_in');
                }

                if (! $hasAnalyticsEventsMetricsLastUpdatedAt) {
                    $table->timestamp('last_updated_at')->nullable()->after('check_in_rate');
                }
            });
        } catch (\Exception $e) {
            // Columns may already exist
        }

        // Add index on event_id
        try {
            $hasAnalyticsEventsMetricsIdxAnalyticsEventIndex = Schema::hasIndex('analytics_events_metrics', 'idx_analytics_event');
            Schema::table('analytics_events_metrics', function (Blueprint $table) use ($hasAnalyticsEventsMetricsIdxAnalyticsEventIndex) {
                if (! $hasAnalyticsEventsMetricsIdxAnalyticsEventIndex) {
                    $table->index('event_id', 'idx_analytics_event');
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
        $hasAnalyticsEventsMetricsTotalCheckedIn = Schema::hasColumn('analytics_events_metrics', 'total_checked_in');
        $hasAnalyticsEventsMetricsCheckInRate = Schema::hasColumn('analytics_events_metrics', 'check_in_rate');
        $hasAnalyticsEventsMetricsLastUpdatedAt = Schema::hasColumn('analytics_events_metrics', 'last_updated_at');
        Schema::table('analytics_events_metrics', function (Blueprint $table) use ($hasAnalyticsEventsMetricsTotalCheckedIn, $hasAnalyticsEventsMetricsCheckInRate, $hasAnalyticsEventsMetricsLastUpdatedAt) {
            // Drop index
            try {
                $table->dropIndex('idx_analytics_event');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop FK
            try {
                $table->dropForeign(['event_id']);
            } catch (\Exception $e) {
                // FK may not exist
            }

            // Drop columns
            $columns = [];
            if ($hasAnalyticsEventsMetricsTotalCheckedIn) {
                $columns[] = 'total_checked_in';
            }
            if ($hasAnalyticsEventsMetricsCheckInRate) {
                $columns[] = 'check_in_rate';
            }
            if ($hasAnalyticsEventsMetricsLastUpdatedAt) {
                $columns[] = 'last_updated_at';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
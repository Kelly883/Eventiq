<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildForSqlite();
            return;
        }

        $this->alterForNonSqlite();
    }

    public function down(): void
    {
        // Intentionally left as no-op. Reverting nullability for production data can be destructive.
    }

    private function alterForNonSqlite(): void
    {
        if (Schema::hasTable('pricing_windows') && Schema::hasColumn('pricing_windows', 'ticket_category_id')) {
            Schema::table('pricing_windows', function (Blueprint $table) {
                $table->dropForeign(['ticket_category_id']);
            });

            Schema::table('pricing_windows', function (Blueprint $table) {
                $table->unsignedBigInteger('ticket_category_id')->nullable()->change();
            });

            Schema::table('pricing_windows', function (Blueprint $table) {
                $table->foreign('ticket_category_id')
                    ->references('id')
                    ->on('ticket_tiers')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('analytics_events_metrics') && Schema::hasColumn('analytics_events_metrics', 'organizer_id')) {
            Schema::table('analytics_events_metrics', function (Blueprint $table) {
                $table->dropForeign(['organizer_id']);
            });

            Schema::table('analytics_events_metrics', function (Blueprint $table) {
                $table->unsignedBigInteger('organizer_id')->nullable()->change();
            });

            Schema::table('analytics_events_metrics', function (Blueprint $table) {
                $table->foreign('organizer_id')
                    ->references('id')
                    ->on('organizers')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('analytics_sales_timeline') && Schema::hasColumn('analytics_sales_timeline', 'ticket_tier_id')) {
            Schema::table('analytics_sales_timeline', function (Blueprint $table) {
                $table->dropForeign(['ticket_tier_id']);
            });

            Schema::table('analytics_sales_timeline', function (Blueprint $table) {
                $table->unsignedBigInteger('ticket_tier_id')->nullable()->change();
            });

            Schema::table('analytics_sales_timeline', function (Blueprint $table) {
                $table->foreign('ticket_tier_id')
                    ->references('id')
                    ->on('ticket_tiers')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('analytics_tier_performance') && Schema::hasColumn('analytics_tier_performance', 'ticket_tier_id')) {
            Schema::table('analytics_tier_performance', function (Blueprint $table) {
                $table->dropForeign(['ticket_tier_id']);
            });

            Schema::table('analytics_tier_performance', function (Blueprint $table) {
                $table->unsignedBigInteger('ticket_tier_id')->nullable()->change();
            });

            Schema::table('analytics_tier_performance', function (Blueprint $table) {
                $table->foreign('ticket_tier_id')
                    ->references('id')
                    ->on('ticket_tiers')
                    ->nullOnDelete();
            });
        }
    }

    private function rebuildForSqlite(): void
    {
        DB::beginTransaction();

        try {
            DB::statement('PRAGMA foreign_keys = OFF');

            $this->rebuildPricingWindows();
            $this->rebuildAnalyticsEventsMetrics();
            $this->rebuildAnalyticsSalesTimeline();
            $this->rebuildAnalyticsTierPerformance();

            DB::statement('PRAGMA foreign_keys = ON');
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            DB::statement('PRAGMA foreign_keys = ON');
            throw $e;
        }
    }

    private function rebuildPricingWindows(): void
    {
        if (!Schema::hasTable('pricing_windows')) {
            return;
        }

        DB::statement('CREATE TABLE pricing_windows_tmp (
            id VARCHAR PRIMARY KEY NOT NULL,
            event_id INTEGER NOT NULL,
            ticket_category_id INTEGER NULL,
            window_name VARCHAR(100) NOT NULL,
            start_date_time DATETIME NOT NULL,
            end_date_time DATETIME NOT NULL,
            price NUMERIC NOT NULL,
            quantity_limit INTEGER NULL,
            quantity_sold INTEGER NOT NULL DEFAULT 0,
            is_active TINYINT NOT NULL DEFAULT 0,
            priority INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL,
            FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY(ticket_category_id) REFERENCES ticket_tiers(id) ON DELETE SET NULL
        )');

        DB::statement('INSERT INTO pricing_windows_tmp (
            id, event_id, ticket_category_id, window_name, start_date_time, end_date_time,
            price, quantity_limit, quantity_sold, is_active, priority, created_at, updated_at, deleted_at
        )
        SELECT
            id, event_id, ticket_category_id, window_name, start_date_time, end_date_time,
            price, quantity_limit, quantity_sold, is_active, priority, created_at, updated_at, deleted_at
        FROM pricing_windows');

        DB::statement('DROP TABLE pricing_windows');
        DB::statement('ALTER TABLE pricing_windows_tmp RENAME TO pricing_windows');

        DB::statement('CREATE INDEX pricing_windows_event_id_index ON pricing_windows(event_id)');
        DB::statement('CREATE INDEX pricing_windows_ticket_category_id_index ON pricing_windows(ticket_category_id)');
        DB::statement('CREATE INDEX pricing_windows_event_id_ticket_category_id_index ON pricing_windows(event_id, ticket_category_id)');
        DB::statement('CREATE INDEX pricing_windows_start_date_time_index ON pricing_windows(start_date_time)');
        DB::statement('CREATE INDEX pricing_windows_end_date_time_index ON pricing_windows(end_date_time)');
        DB::statement('CREATE INDEX pricing_windows_deleted_at_index ON pricing_windows(deleted_at)');
        DB::statement('CREATE INDEX idx_windows_active_daterange ON pricing_windows(is_active, start_date_time, end_date_time)');
        DB::statement('CREATE INDEX idx_windows_event_active ON pricing_windows(event_id, is_active)');
        DB::statement('CREATE INDEX idx_windows_tier_active_daterange ON pricing_windows(ticket_category_id, is_active, start_date_time, end_date_time)');
        DB::statement('CREATE INDEX idx_windows_event_priority_start ON pricing_windows(event_id, priority, start_date_time)');
    }

    private function rebuildAnalyticsEventsMetrics(): void
    {
        if (!Schema::hasTable('analytics_events_metrics')) {
            return;
        }

        DB::statement('CREATE TABLE analytics_events_metrics_tmp (
            id VARCHAR PRIMARY KEY NOT NULL,
            event_id INTEGER NOT NULL,
            organizer_id INTEGER NULL,
            total_revenue NUMERIC NOT NULL DEFAULT 0,
            total_tickets_sold INTEGER NOT NULL DEFAULT 0,
            total_page_views INTEGER NOT NULL DEFAULT 0,
            total_ticket_page_views INTEGER NOT NULL DEFAULT 0,
            conversion_rate NUMERIC NOT NULL DEFAULT 0,
            average_ticket_price NUMERIC NOT NULL DEFAULT 0,
            peak_sales_hour INTEGER NULL,
            top_ticket_tier_id INTEGER NULL,
            last_updated_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY(organizer_id) REFERENCES organizers(id) ON DELETE SET NULL,
            FOREIGN KEY(top_ticket_tier_id) REFERENCES ticket_tiers(id) ON DELETE SET NULL
        )');

        DB::statement('INSERT INTO analytics_events_metrics_tmp (
            id, event_id, organizer_id, total_revenue, total_tickets_sold, total_page_views,
            total_ticket_page_views, conversion_rate, average_ticket_price, peak_sales_hour,
            top_ticket_tier_id, last_updated_at, created_at, updated_at
        )
        SELECT
            id, event_id, organizer_id, total_revenue, total_tickets_sold, total_page_views,
            total_ticket_page_views, conversion_rate, average_ticket_price, peak_sales_hour,
            top_ticket_tier_id, last_updated_at, created_at, updated_at
        FROM analytics_events_metrics');

        DB::statement('DROP TABLE analytics_events_metrics');
        DB::statement('ALTER TABLE analytics_events_metrics_tmp RENAME TO analytics_events_metrics');

        DB::statement('CREATE INDEX analytics_events_metrics_event_id_index ON analytics_events_metrics(event_id)');
        DB::statement('CREATE INDEX analytics_events_metrics_organizer_id_index ON analytics_events_metrics(organizer_id)');
        DB::statement('CREATE INDEX idx_metrics_organizer_event ON analytics_events_metrics(organizer_id, event_id)');
        DB::statement('CREATE INDEX idx_metrics_organizer_updated ON analytics_events_metrics(organizer_id, last_updated_at)');
    }

    private function rebuildAnalyticsSalesTimeline(): void
    {
        if (!Schema::hasTable('analytics_sales_timeline')) {
            return;
        }

        DB::statement('CREATE TABLE analytics_sales_timeline_tmp (
            id VARCHAR PRIMARY KEY NOT NULL,
            event_id INTEGER NOT NULL,
            ticket_tier_id INTEGER NULL,
            pricing_window_id INTEGER NULL,
            sale_timestamp DATETIME NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price NUMERIC NOT NULL,
            total_amount NUMERIC NOT NULL,
            buyer_email VARCHAR(255) NULL,
            source VARCHAR(100) NULL,
            created_at DATETIME NULL,
            FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY(ticket_tier_id) REFERENCES ticket_tiers(id) ON DELETE SET NULL,
            FOREIGN KEY(pricing_window_id) REFERENCES pricing_windows(id) ON DELETE SET NULL
        )');

        DB::statement('INSERT INTO analytics_sales_timeline_tmp (
            id, event_id, ticket_tier_id, pricing_window_id, sale_timestamp,
            quantity, unit_price, total_amount, buyer_email, source, created_at
        )
        SELECT
            id, event_id, ticket_tier_id, pricing_window_id, sale_timestamp,
            quantity, unit_price, total_amount, buyer_email, source, created_at
        FROM analytics_sales_timeline');

        DB::statement('DROP TABLE analytics_sales_timeline');
        DB::statement('ALTER TABLE analytics_sales_timeline_tmp RENAME TO analytics_sales_timeline');

        DB::statement('CREATE INDEX analytics_sales_timeline_event_id_index ON analytics_sales_timeline(event_id)');
        DB::statement('CREATE INDEX analytics_sales_timeline_sale_timestamp_index ON analytics_sales_timeline(sale_timestamp)');
        DB::statement('CREATE INDEX idx_sales_timeline_event_timestamp ON analytics_sales_timeline(event_id, sale_timestamp)');
        DB::statement('CREATE INDEX idx_sales_timeline_full ON analytics_sales_timeline(event_id, ticket_tier_id, sale_timestamp)');
    }

    private function rebuildAnalyticsTierPerformance(): void
    {
        if (!Schema::hasTable('analytics_tier_performance')) {
            return;
        }

        DB::statement('CREATE TABLE analytics_tier_performance_tmp (
            id VARCHAR PRIMARY KEY NOT NULL,
            event_id INTEGER NOT NULL,
            ticket_tier_id INTEGER NULL,
            total_sold INTEGER NOT NULL DEFAULT 0,
            total_revenue NUMERIC NOT NULL DEFAULT 0,
            average_price NUMERIC NOT NULL DEFAULT 0,
            percentage_of_total_sales NUMERIC NOT NULL DEFAULT 0,
            percentage_of_total_revenue NUMERIC NOT NULL DEFAULT 0,
            conversion_rate NUMERIC NOT NULL DEFAULT 0,
            last_updated_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY(ticket_tier_id) REFERENCES ticket_tiers(id) ON DELETE SET NULL
        )');

        DB::statement('INSERT INTO analytics_tier_performance_tmp (
            id, event_id, ticket_tier_id, total_sold, total_revenue, average_price,
            percentage_of_total_sales, percentage_of_total_revenue, conversion_rate,
            last_updated_at, created_at, updated_at
        )
        SELECT
            id, event_id, ticket_tier_id, total_sold, total_revenue, average_price,
            percentage_of_total_sales, percentage_of_total_revenue, conversion_rate,
            last_updated_at, created_at, updated_at
        FROM analytics_tier_performance');

        DB::statement('DROP TABLE analytics_tier_performance');
        DB::statement('ALTER TABLE analytics_tier_performance_tmp RENAME TO analytics_tier_performance');

        DB::statement('CREATE INDEX analytics_tier_performance_event_id_index ON analytics_tier_performance(event_id)');
        DB::statement('CREATE INDEX analytics_tier_performance_ticket_tier_id_index ON analytics_tier_performance(ticket_tier_id)');
        DB::statement('CREATE INDEX idx_tier_perf_event_tier ON analytics_tier_performance(event_id, ticket_tier_id)');
    }
};

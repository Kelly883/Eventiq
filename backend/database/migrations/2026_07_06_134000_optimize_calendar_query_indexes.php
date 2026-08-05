<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // 1. EVENTS TABLE INDEXES
        // ============================================================
        // The EventCalendarService filters primarily by:
        //   - status (default: 'published')
        //   - start_datetime (range queries for date windows)
        //   - category (optional filter)
        //   - organizer_id (optional filter)
        // And orders by start_datetime ASC/DESC

        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'status') && Schema::hasColumn('events', 'start_datetime')) {
                try {
                    $table->index(['status', 'start_datetime'], 'idx_events_status_start_datetime');
                } catch (\Throwable $e) {
                }
            }

            if (Schema::hasColumn('events', 'status') && Schema::hasColumn('events', 'category')) {
                try {
                    $table->index(['status', 'category'], 'idx_events_status_category');
                } catch (\Throwable $e) {
                }
            }

            if (Schema::hasColumn('events', 'start_datetime')) {
                try {
                    $table->index(['start_datetime'], 'idx_events_start_datetime');
                } catch (\Throwable $e) {
                }
            }

            if (Schema::hasColumn('events', 'status')) {
                try {
                    $table->index(['status'], 'idx_events_status');
                } catch (\Throwable $e) {
                }
            }

            if (Schema::hasColumn('events', 'organizer_id')
                && Schema::hasColumn('events', 'status')
                && Schema::hasColumn('events', 'start_datetime')) {
                try {
                    $table->index(['organizer_id', 'status', 'start_datetime'], 'idx_events_organizer_status_date');
                } catch (\Throwable $e) {
                }
            }

            if (Schema::hasColumn('events', 'category')) {
                try {
                    $table->index(['category'], 'idx_events_category');
                } catch (\Throwable $e) {
                }
            }
        });

        // ============================================================
        // 2. TICKET_INVENTORY TABLE INDEXES
        // ============================================================
        // The calendar LEFT JOINs a ticket_inventory aggregate
        // (SUM(total_available), SUM(total_sold)) grouped by event_id.
        // Also supports per-tier lookups via ticket_tier_id.

        Schema::table('ticket_inventory', function (Blueprint $table) {
            // Composite index: (event_id, ticket_tier_id).
            // Used when joining events -> inventory for stock levels,
            // and for per-tier availability lookups on the event detail page.
            if (Schema::hasColumn('ticket_inventory', 'event_id') && Schema::hasColumn('ticket_inventory', 'ticket_tier_id')) {
                try {
                    $table->index(['event_id', 'ticket_tier_id'], 'idx_ticket_inventory_event_tier');
                } catch (\Throwable $e) {
                }
            }

            // Standalone index on event_id.
            // Speeds up the GROUP BY event_id aggregate in the
            // calendar service when joining to inventory.
            if (Schema::hasColumn('ticket_inventory', 'event_id')) {
                try {
                    $table->index(['event_id'], 'idx_ticket_inventory_event_id');
                } catch (\Throwable $e) {
                }
            }

            // Optional composite: (event_id, total_available) covering
            // index for the aggregate SUM().  Not universally supported
            // across all DB drivers (SQLite vs MySQL), so only add
            // if we detect MySQL/Postgres.
            if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'pgsql') {
                try {
                    DB::statement("
                        CREATE INDEX idx_ticket_inventory_event_available_covering
                        ON ticket_inventory (event_id)
                        INCLUDE (total_available, total_sold)
                    ");
                } catch (\Throwable $e) {
                    // MySQL doesn't support INCLUDE; try a composite instead.
                    if (Schema::hasColumn('ticket_inventory', 'event_id')
                        && Schema::hasColumn('ticket_inventory', 'total_available')
                        && Schema::hasColumn('ticket_inventory', 'total_sold')) {
                        try {
                            $table->index(['event_id', 'total_available', 'total_sold'], 'idx_ticket_inventory_event_stocks');
                        } catch (\Throwable $e2) {
                        }
                    }
                }
            }
        });

        // ============================================================
        // 3. PRICING_WINDOWS TABLE INDEXES
        // ============================================================
        // The calendar LEFT JOINs a pricing_windows aggregate
        // (MIN(price), MAX(price)) grouped by event_id where
        // is_active = true AND deleted_at IS NULL AND within date range.

        Schema::table('pricing_windows', function (Blueprint $table) {
            // Composite index: (event_id, ticket_category_id).
            // Used by the event detail page to look up the currently
            // active pricing window for a specific ticket tier.
            // Note: The legacy column in pricing_windows is ticket_category_id
            // (aliased as ticket_tier_id in the model relationship).
            if (Schema::hasColumn('pricing_windows', 'event_id') && Schema::hasColumn('pricing_windows', 'ticket_category_id')) {
                try {
                    $table->index(['event_id', 'ticket_category_id'], 'idx_pricing_windows_event_tier');
                } catch (\Throwable $e) {
                }
            }

            // Standalone index on event_id.
            // Primary join key for the calendar price-aggregate subquery.
            if (Schema::hasColumn('pricing_windows', 'event_id')) {
                try {
                    $table->index(['event_id'], 'idx_pricing_windows_event_id');
                } catch (\Throwable $e) {
                }
            }

            // Composite index for active-window lookup.
            // Optimizes the EventCalendarService's pricing subquery which
            // filters: WHERE is_active = TRUE AND deleted_at IS NULL.
            if (Schema::hasColumn('pricing_windows', 'event_id')
                && Schema::hasColumn('pricing_windows', 'is_active')
                && Schema::hasColumn('pricing_windows', 'deleted_at')) {
                try {
                    $table->index(['event_id', 'is_active', 'deleted_at'], 'idx_pricing_windows_active_event');
                } catch (\Throwable $e) {
                }
            }

            // Composite index for time-window filtering.
            // Supports scopes that need "windows currently active by date".
            if (Schema::hasColumn('pricing_windows', 'event_id')
                && Schema::hasColumn('pricing_windows', 'start_date_time')
                && Schema::hasColumn('pricing_windows', 'end_date_time')) {
                try {
                    $table->index(['event_id', 'start_date_time', 'end_date_time'], 'idx_pricing_windows_event_dates');
                } catch (\Throwable $e) {
                }
            } elseif (Schema::hasColumn('pricing_windows', 'event_id')
                && Schema::hasColumn('pricing_windows', 'start_date')
                && Schema::hasColumn('pricing_windows', 'end_date')) {
                try {
                    $table->index(['event_id', 'start_date', 'end_date'], 'idx_pricing_windows_event_dates_old');
                } catch (\Throwable $e2) {
                }
            }
        });

        // ============================================================
        // 4. ORGANIZERS TABLE — Primary key index should already exist.
        // ============================================================
        // (organizers.id is already a PK, so no additional index needed.
        //  We add a name lookup index in case the calendar supports
        //  organizer-name searches.)

        Schema::table('organizers', function (Blueprint $table) {
            try {
                // Only create an index if a name column exists.
                if (Schema::hasColumn('organizers', 'name')) {
                    $table->index(['name'], 'idx_organizers_name');
                }
            } catch (\Throwable $e) {
                // Safe to skip.
            }
        });

        // ============================================================
        // 5. EVENTS_CALENDAR_SUMMARY — ensure index on event_date.
        // ============================================================
        if (Schema::hasTable('events_calendar_summary')) {
            Schema::table('events_calendar_summary', function (Blueprint $table) {
                try {
                    $table->index(['event_date'], 'idx_events_calendar_summary_date');
                } catch (\Throwable $e) {
                    // event_date is likely already the PK or unique — safe.
                }

                try {
                    $table->index(['event_date', 'published_events'], 'idx_events_calendar_summary_date_pub');
                } catch (\Throwable $e) {
                    // Safe to skip.
                }
            });
        }
    }

    public function down(): void
    {
        // Rollback — drop all indexes we created.
        Schema::table('events', function (Blueprint $table) {
            $safeDrop = function ($name, $table) {
                try {
                    $table->dropIndex($name);
                } catch (\Throwable $e) {
                    // Index may never have been created — safe.
                }
            };

            $safeDrop('idx_events_status_start_datetime', $table);
            $safeDrop('idx_events_status_category', $table);
            $safeDrop('idx_events_start_datetime', $table);
            $safeDrop('idx_events_status', $table);
            $safeDrop('idx_events_organizer_status_date', $table);
            $safeDrop('idx_events_category', $table);
        });

        Schema::table('ticket_inventory', function (Blueprint $table) {
            $safeDrop = function ($name, $table) {
                try {
                    $table->dropIndex($name);
                } catch (\Throwable $e) {
                    // Index may never have been created — safe.
                }
            };

            $safeDrop('idx_ticket_inventory_event_tier', $table);
            $safeDrop('idx_ticket_inventory_event_id', $table);
            try {
                $table->dropIndex('idx_ticket_inventory_event_available_covering');
            } catch (\Throwable $e) {
                // Safe to skip.
            }
            try {
                $table->dropIndex('idx_ticket_inventory_event_stocks');
            } catch (\Throwable $e) {
                // Safe to skip.
            }
        });

        Schema::table('pricing_windows', function (Blueprint $table) {
            $safeDrop = function ($name, $table) {
                try {
                    $table->dropIndex($name);
                } catch (\Throwable $e) {
                    // Index may never have been created — safe.
                }
            };

            $safeDrop('idx_pricing_windows_event_tier', $table);
            $safeDrop('idx_pricing_windows_event_id', $table);
            $safeDrop('idx_pricing_windows_active_event', $table);
            try {
                $table->dropIndex('idx_pricing_windows_event_dates');
            } catch (\Throwable $e) {
                // Safe to skip.
            }
            try {
                $table->dropIndex('idx_pricing_windows_event_dates_old');
            } catch (\Throwable $e) {
                // Safe to skip.
            }
        });

        Schema::table('organizers', function (Blueprint $table) {
            try {
                if (Schema::hasColumn('organizers', 'name')) {
                    $table->dropIndex('idx_organizers_name');
                }
            } catch (\Throwable $e) {
                // Safe to skip.
            }
        });

        if (Schema::hasTable('events_calendar_summary')) {
            Schema::table('events_calendar_summary', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_events_calendar_summary_date');
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropIndex('idx_events_calendar_summary_date_pub');
                } catch (\Throwable $e) {
                }
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 64 – Finalise events table schema.
 *
 * Remaining gaps after prior incremental migrations:
 *  1. Rename latitude/longitude → venue_latitude/venue_longitude to match the canonical schema.
 *  2. Add index on `title` for full-text / prefix searches.
 *  3. Change `description` to longText so large HTML content is not silently truncated on MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            // 1. Rename lat/lng columns to venue_latitude / venue_longitude.
            if (Schema::hasColumn('events', 'latitude') && !Schema::hasColumn('events', 'venue_latitude')) {
                $table->renameColumn('latitude', 'venue_latitude');
            }

            if (Schema::hasColumn('events', 'longitude') && !Schema::hasColumn('events', 'venue_longitude')) {
                $table->renameColumn('longitude', 'venue_longitude');
            }
        });

        Schema::table('events', function (Blueprint $table) {
            // 2. Widen description to longText for large event descriptions on MySQL.
            if (Schema::hasColumn('events', 'description')) {
                $table->longText('description')->nullable()->change();
            }

            // 3. Index on title for search (prefix / LIKE queries).
            if (Schema::hasColumn('events', 'title') && !$this->indexExists('events', 'events_title_index')) {
                $table->index('title', 'events_title_index');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            if ($this->indexExists('events', 'events_title_index')) {
                $table->dropIndex('events_title_index');
            }

            if (Schema::hasColumn('events', 'description')) {
                $table->text('description')->nullable()->change();
            }
        });

        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'venue_latitude') && !Schema::hasColumn('events', 'latitude')) {
                $table->renameColumn('venue_latitude', 'latitude');
            }

            if (Schema::hasColumn('events', 'venue_longitude') && !Schema::hasColumn('events', 'longitude')) {
                $table->renameColumn('venue_longitude', 'longitude');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $row = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $indexName]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );

        return $row !== null;
    }
};

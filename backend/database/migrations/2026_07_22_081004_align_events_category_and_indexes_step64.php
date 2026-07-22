<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        if (!Schema::hasColumn('events', 'category')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('category')->nullable()->after('status');
            });
        }

        if (!$this->indexExists('events', 'events_category_index') && Schema::hasColumn('events', 'category')) {
            Schema::table('events', function (Blueprint $table) {
                $table->index('category', 'events_category_index');
            });
        }

        if (!$this->indexExists('events', 'events_venue_address_index') && Schema::hasColumn('events', 'venue_address')) {
            Schema::table('events', function (Blueprint $table) {
                $table->index('venue_address', 'events_venue_address_index');
            });
        }

        // Re-introduce this composite index against the actual category column used by the Event model.
        if (!$this->indexExists('events', 'idx_events_status_category')
            && Schema::hasColumn('events', 'status')
            && Schema::hasColumn('events', 'category')) {
            Schema::table('events', function (Blueprint $table) {
                $table->index(['status', 'category'], 'idx_events_status_category');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        if ($this->indexExists('events', 'idx_events_status_category')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropIndex('idx_events_status_category');
            });
        }

        if ($this->indexExists('events', 'events_venue_address_index')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropIndex('events_venue_address_index');
            });
        }

        if ($this->indexExists('events', 'events_category_index')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropIndex('events_category_index');
            });
        }

        if (Schema::hasColumn('events', 'category')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

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

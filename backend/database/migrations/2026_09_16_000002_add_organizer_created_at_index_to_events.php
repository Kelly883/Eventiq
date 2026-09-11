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

        // Index for organizer event listing ordered by created_at (used in index pagination)
        // Existing indexes cover (organizer_id,status) and (organizer_id,start_datetime) but not (organizer_id,created_at)
        if (!$this->indexExists('events', 'idx_events_organizer_created_at')) {
            Schema::table('events', function (Blueprint $table) {
                $table->index(['organizer_id', 'created_at'], 'idx_events_organizer_created_at');
            });
        }

        // Also ensure slug index exists for concurrent duplicate check
        if (Schema::hasColumn('events', 'slug') && !$this->indexExists('events', 'events_slug_unique')) {
            try {
                Schema::table('events', function (Blueprint $table) {
                    $table->unique('slug', 'events_slug_unique');
                });
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        if ($this->indexExists('events', 'idx_events_organizer_created_at')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropIndex('idx_events_organizer_created_at');
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

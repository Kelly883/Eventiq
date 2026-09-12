<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ticket_tiers')) return;

        // Drop old unique that doesn't consider soft deletes
        try {
            Schema::table('ticket_tiers', function (Blueprint $table) {
                $table->dropUnique(['event_id', 'name']);
            });
        } catch (\Throwable $e) {
            // Try alternative index name
            try {
                DB::statement('DROP INDEX IF EXISTS ticket_tiers_event_id_name_unique');
            } catch (\Throwable $e2) {}
        }

        // New unique that allows same name if one is soft deleted (deleted_at differs)
        // For PostgreSQL this would be a partial index WHERE deleted_at IS NULL, but for SQLite
        // we include deleted_at in the unique so (event_id, name, deleted_at) are unique.
        // Active rows have deleted_at = NULL, soft deleted have timestamp, so they don't conflict.
        try {
            Schema::table('ticket_tiers', function (Blueprint $table) {
                $table->unique(['event_id', 'name', 'deleted_at'], 'ticket_tiers_event_name_deleted_at_unique');
            });
        } catch (\Throwable $e) {
            // If already exists or fails, ignore
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('ticket_tiers')) return;
        try {
            Schema::table('ticket_tiers', function (Blueprint $table) {
                $table->dropUnique('ticket_tiers_event_name_deleted_at_unique');
            });
        } catch (\Throwable $e) {}
        try {
            Schema::table('ticket_tiers', function (Blueprint $table) {
                $table->unique(['event_id', 'name']);
            });
        } catch (\Throwable $e) {}
    }
};

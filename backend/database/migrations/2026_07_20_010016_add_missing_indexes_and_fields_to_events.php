<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add deleted_at if missing (SoftDeletes is intentional per Event model)
        if (!Schema::hasColumn('events', 'deleted_at')) {
            Schema::table('events', function (Blueprint $table) {
                $table->timestamp('deleted_at')->nullable()->after('updated_at');
            });
        }

        // Check existing indexes to avoid duplicate creation
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            $existingIndexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'events'");
        } elseif ($driver === 'mysql') {
            $existingIndexes = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events'");
        } else {
            $existingIndexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='events'");
        }
        $existingIndexNames = array_column($existingIndexes, 'indexname');

        // Check columns that should exist at this migration point
        $hasDeletedAt = Schema::hasColumn('events', 'deleted_at');
        $hasStartDatetime = Schema::hasColumn('events', 'start_datetime');
        $hasOrganizerId = Schema::hasColumn('events', 'organizer_id');

        Schema::table('events', function (Blueprint $table) use ($hasDeletedAt, $hasStartDatetime, $hasOrganizerId, $existingIndexNames) {
            // Create indexes only for columns that exist and indexes that don't already exist
            if ($hasDeletedAt && !in_array('events_deleted_at_index', $existingIndexNames)) {
                $table->index('deleted_at', 'events_deleted_at_index');
            }
            
            if ($hasStartDatetime && !in_array('events_start_datetime_index', $existingIndexNames)) {
                $table->index('start_datetime', 'events_start_datetime_index');
            }
            
            if ($hasOrganizerId && !in_array('events_organizer_id_index', $existingIndexNames)) {
                $table->index('organizer_id', 'events_organizer_id_index');
            }

            // Missing fields for a real event system
            $table->string('timezone', 50)->nullable()->after('end_datetime');
            $table->string('currency', 3)->default('NGN')->after('timezone');
            $table->string('slug', 255)->unique()->nullable()->after('title');
            $table->text('cancellation_reason')->nullable()->after('status');
            $table->unsignedInteger('max_tickets_per_order')->nullable()->after('capacity');
            $table->string('age_restriction', 10)->nullable()->after('max_tickets_per_order');
            $table->json('tags')->nullable()->after('age_restriction');
            $table->decimal('latitude', 10, 7)->nullable()->after('venue_address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            $existingIndexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'events'");
        } elseif ($driver === 'mysql') {
            $existingIndexes = DB::select("SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events'");
        } else {
            $existingIndexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='events'");
        }
        $existingIndexNames = array_column($existingIndexes, 'indexname');

        Schema::table('events', function (Blueprint $table) use ($existingIndexNames) {
            if (in_array('events_deleted_at_index', $existingIndexNames)) {
                $table->dropIndex(['events_deleted_at_index']);
            }
            if (in_array('events_start_datetime_index', $existingIndexNames)) {
                $table->dropIndex(['events_start_datetime_index']);
            }
            if (in_array('events_organizer_id_index', $existingIndexNames)) {
                $table->dropIndex(['events_organizer_id_index']);
            }

            $table->dropColumn([
                'timezone',
                'currency',
                'slug',
                'cancellation_reason',
                'max_tickets_per_order',
                'age_restriction',
                'tags',
                'latitude',
                'longitude',
            ]);
        });
    }
};

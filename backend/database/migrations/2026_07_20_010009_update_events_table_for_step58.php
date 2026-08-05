<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropCalendarViews();

        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->renameColumn('location', 'venue_name');
            $table->string('venue_address')->nullable()->after('venue_name');
            $table->renameColumn('banner_path', 'banner_image_url');
            $table->unsignedInteger('capacity')->nullable(false)->change();
            $table->timestamp('deleted_at')->nullable()->after('updated_at');
        });

        $this->safeAddIndex('events', ['user_id'], 'events_user_id_index');
        $this->safeAddIndex('events', ['status'], 'events_status_index');
        $this->safeAddIndex('events', ['organizer_id', 'status'], 'events_organizer_id_status_index');
    }

    public function down(): void
    {
        $this->dropCalendarViews();

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['events_user_id_index']);
            $table->dropIndex(['events_status_index']);
            $table->dropIndex(['events_organizer_id_status_index']);

            $table->dropColumn(['user_id', 'venue_address', 'deleted_at']);

            $table->renameColumn('venue_name', 'location');
            $table->renameColumn('banner_image_url', 'banner_path');
            $table->unsignedInteger('capacity')->nullable()->change();
        });
    }

    private function dropCalendarViews(): void
    {
        DB::statement('DROP VIEW IF EXISTS calendar_events_availability');
        DB::statement('DROP VIEW IF EXISTS calendar_event_availability_view');
        DB::statement('DROP VIEW IF EXISTS calendar_date_availability_summary_view');
    }

    private function safeAddIndex(string $tableName, array $columns, string $indexName): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
        }
    }
};

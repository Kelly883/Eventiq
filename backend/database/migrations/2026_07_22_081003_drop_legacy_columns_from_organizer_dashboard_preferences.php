<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('organizer_dashboard_preferences')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->dropInvalidSqliteIndexes();
        }

        $hasOrganizerDashboardPreferencesUserId = Schema::hasColumn('organizer_dashboard_preferences', 'user_id');
        $hasOrganizerDashboardPreferencesPreferences = Schema::hasColumn('organizer_dashboard_preferences', 'preferences');
        Schema::table('organizer_dashboard_preferences', function (Blueprint $table) use ($hasOrganizerDashboardPreferencesUserId, $hasOrganizerDashboardPreferencesPreferences) {
            if ($hasOrganizerDashboardPreferencesUserId) {
                $table->dropConstrainedForeignId('user_id');
            }

            if ($hasOrganizerDashboardPreferencesPreferences) {
                $table->dropColumn('preferences');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('organizer_dashboard_preferences')) {
            return;
        }

        $hasOrganizerDashboardPreferencesUserId = Schema::hasColumn('organizer_dashboard_preferences', 'user_id');
        $hasOrganizerDashboardPreferencesPreferences = Schema::hasColumn('organizer_dashboard_preferences', 'preferences');
        Schema::table('organizer_dashboard_preferences', function (Blueprint $table) use ($hasOrganizerDashboardPreferencesUserId, $hasOrganizerDashboardPreferencesPreferences) {
            if (!$hasOrganizerDashboardPreferencesUserId) {
                $table->uuid('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }

            if (!$hasOrganizerDashboardPreferencesPreferences) {
                $table->json('preferences')->nullable()->after('user_id');
            }
        });
    }

    private function dropInvalidSqliteIndexes(): void
    {
        $indexes = DB::select("SELECT name, tbl_name FROM sqlite_master WHERE type = 'index' AND sql IS NOT NULL");

        foreach ($indexes as $index) {
            $table = $index->tbl_name;
            $indexName = $index->name;

            $tableColumns = collect(DB::select("PRAGMA table_info('{$table}')"))
                ->map(fn ($column) => $column->name)
                ->all();

            if (empty($tableColumns)) {
                continue;
            }

            $indexColumns = DB::select("PRAGMA index_info('{$indexName}')");
            $isInvalid = false;

            foreach ($indexColumns as $indexColumn) {
                $columnName = $indexColumn->name ?? null;

                if ($columnName === null || !in_array($columnName, $tableColumns, true)) {
                    $isInvalid = true;
                    break;
                }
            }

            if ($isInvalid) {
                DB::statement("DROP INDEX IF EXISTS {$indexName}");
            }
        }
    }
};

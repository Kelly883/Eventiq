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

        Schema::table('organizer_dashboard_preferences', function (Blueprint $table) {
            if (Schema::hasColumn('organizer_dashboard_preferences', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            if (Schema::hasColumn('organizer_dashboard_preferences', 'preferences')) {
                $table->dropColumn('preferences');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('organizer_dashboard_preferences')) {
            return;
        }

        Schema::table('organizer_dashboard_preferences', function (Blueprint $table) {
            if (!Schema::hasColumn('organizer_dashboard_preferences', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }

            if (!Schema::hasColumn('organizer_dashboard_preferences', 'preferences')) {
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

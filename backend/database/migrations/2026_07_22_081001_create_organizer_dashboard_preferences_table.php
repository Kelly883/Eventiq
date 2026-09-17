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
			Schema::create('organizer_dashboard_preferences', function (Blueprint $table) {
				$table->id();
				$table->foreignId('organizer_id')->constrained()->cascadeOnDelete();
				$table->string('default_event_filter')->default('all');
				$table->string('default_date_range')->default('30days');
				$table->foreignId('expanded_event_id')->nullable()->constrained('events')->nullOnDelete();
				$table->boolean('show_activity_feed')->default(true);
				$table->boolean('auto_refresh_enabled')->default(true);
				$table->timestamps();

				$table->unique('organizer_id', 'organizer_dashboard_preferences_organizer_id_unique');
			});

			return;
		}

		$hasOrganizerDashboardPreferencesOrganizerId = Schema::hasColumn('organizer_dashboard_preferences', 'organizer_id');
		$hasOrganizerDashboardPreferencesDefaultEventFilter = Schema::hasColumn('organizer_dashboard_preferences', 'default_event_filter');
		$hasOrganizerDashboardPreferencesDefaultDateRange = Schema::hasColumn('organizer_dashboard_preferences', 'default_date_range');
		$hasOrganizerDashboardPreferencesExpandedEventId = Schema::hasColumn('organizer_dashboard_preferences', 'expanded_event_id');
		$hasOrganizerDashboardPreferencesShowActivityFeed = Schema::hasColumn('organizer_dashboard_preferences', 'show_activity_feed');
		$hasOrganizerDashboardPreferencesAutoRefreshEnabled = Schema::hasColumn('organizer_dashboard_preferences', 'auto_refresh_enabled');
		Schema::table('organizer_dashboard_preferences', function (Blueprint $table) use ($hasOrganizerDashboardPreferencesOrganizerId, $hasOrganizerDashboardPreferencesDefaultEventFilter, $hasOrganizerDashboardPreferencesDefaultDateRange, $hasOrganizerDashboardPreferencesExpandedEventId, $hasOrganizerDashboardPreferencesShowActivityFeed, $hasOrganizerDashboardPreferencesAutoRefreshEnabled) {
			if (!$hasOrganizerDashboardPreferencesOrganizerId) {
				$table->foreignId('organizer_id')->nullable()->after('id');
			}

			if (!$hasOrganizerDashboardPreferencesDefaultEventFilter) {
				$table->string('default_event_filter')->default('all')->after('organizer_id');
			}

			if (!$hasOrganizerDashboardPreferencesDefaultDateRange) {
				$table->string('default_date_range')->default('30days')->after('default_event_filter');
			}

			if (!$hasOrganizerDashboardPreferencesExpandedEventId) {
				$table->foreignId('expanded_event_id')->nullable()->after('default_date_range');
			}

			if (!$hasOrganizerDashboardPreferencesShowActivityFeed) {
				$table->boolean('show_activity_feed')->default(true)->after('expanded_event_id');
			}

			if (!$hasOrganizerDashboardPreferencesAutoRefreshEnabled) {
				$table->boolean('auto_refresh_enabled')->default(true)->after('show_activity_feed');
			}
		});

		if (Schema::hasColumn('organizer_dashboard_preferences', 'user_id')) {
			// Backfill organizer_id for existing rows so preferences remain tied to the same owner.
			DB::statement('UPDATE organizer_dashboard_preferences SET organizer_id = (SELECT id FROM organizers WHERE organizers.user_id = organizer_dashboard_preferences.user_id) WHERE organizer_id IS NULL');
		}

		if (!$this->indexExists('organizer_dashboard_preferences', 'organizer_dashboard_preferences_organizer_id_unique')) {
			Schema::table('organizer_dashboard_preferences', function (Blueprint $table) {
				$table->unique('organizer_id', 'organizer_dashboard_preferences_organizer_id_unique');
			});
		}

		if (!$this->foreignKeyExists('organizer_dashboard_preferences', 'organizer_id')) {
			Schema::table('organizer_dashboard_preferences', function (Blueprint $table) {
				$table->foreign('organizer_id')->references('id')->on('organizers')->cascadeOnDelete();
			});
		}

		if (!$this->foreignKeyExists('organizer_dashboard_preferences', 'expanded_event_id')) {
			Schema::table('organizer_dashboard_preferences', function (Blueprint $table) {
				$table->foreign('expanded_event_id')->references('id')->on('events')->nullOnDelete();
			});
		}
	}

	public function down(): void
	{
		if (!Schema::hasTable('organizer_dashboard_preferences')) {
			return;
		}

		if ($this->foreignKeyExists('organizer_dashboard_preferences', 'expanded_event_id')) {
			Schema::table('organizer_dashboard_preferences', function (Blueprint $table) {
				$table->dropForeign(['expanded_event_id']);
			});
		}

		if ($this->foreignKeyExists('organizer_dashboard_preferences', 'organizer_id')) {
			Schema::table('organizer_dashboard_preferences', function (Blueprint $table) {
				$table->dropForeign(['organizer_id']);
			});
		}

		if ($this->indexExists('organizer_dashboard_preferences', 'organizer_dashboard_preferences_organizer_id_unique')) {
			Schema::table('organizer_dashboard_preferences', function (Blueprint $table) {
				$table->dropUnique('organizer_dashboard_preferences_organizer_id_unique');
			});
		}
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

		if (DB::getDriverName() === 'pgsql') {
			$row = DB::selectOne(
				'SELECT i.relname FROM pg_index x '
				. 'JOIN pg_class i ON x.indexrelid = i.oid '
				. 'JOIN pg_class t ON x.indrelid = t.oid '
				. 'JOIN pg_namespace n ON t.relnamespace = n.oid '
				. 'WHERE n.nspname = current_schema() '
				. 'AND t.relname = ? '
				. 'AND i.relname = ?',
				[$table, $indexName]
			);

			return $row !== null;
		}

		$row = DB::selectOne(
			'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
			[$table, $indexName]
		);

		return $row !== null;
	}

    private function foreignKeyExists(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $rows = DB::select("PRAGMA foreign_key_list('{$table}')");
            foreach ($rows as $row) {
                if (($row->from ?? null) === $column) {
                    return true;
                }
            }

            return false;
        }

        $row = DB::selectOne(
            'SELECT kcu.column_name FROM information_schema.table_constraints tc JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name WHERE tc.table_schema = current_schema() AND tc.table_name = ? AND kcu.column_name = ? AND tc.constraint_type = ?',
            [$table, $column, 'FOREIGN KEY']
        );

        return $row !== null;
    }
};

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

		Schema::table('organizer_dashboard_preferences', function (Blueprint $table) {
			if (!Schema::hasColumn('organizer_dashboard_preferences', 'organizer_id')) {
				$table->foreignId('organizer_id')->nullable()->after('id');
			}

			if (!Schema::hasColumn('organizer_dashboard_preferences', 'default_event_filter')) {
				$table->string('default_event_filter')->default('all')->after('organizer_id');
			}

			if (!Schema::hasColumn('organizer_dashboard_preferences', 'default_date_range')) {
				$table->string('default_date_range')->default('30days')->after('default_event_filter');
			}

			if (!Schema::hasColumn('organizer_dashboard_preferences', 'expanded_event_id')) {
				$table->foreignId('expanded_event_id')->nullable()->after('default_date_range');
			}

			if (!Schema::hasColumn('organizer_dashboard_preferences', 'show_activity_feed')) {
				$table->boolean('show_activity_feed')->default(true)->after('expanded_event_id');
			}

			if (!Schema::hasColumn('organizer_dashboard_preferences', 'auto_refresh_enabled')) {
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

		$row = DB::selectOne(
			'SELECT index_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
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
			'SELECT column_name FROM information_schema.key_column_usage WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? AND referenced_table_name IS NOT NULL',
			[$table, $column]
		);

		return $row !== null;
	}
};

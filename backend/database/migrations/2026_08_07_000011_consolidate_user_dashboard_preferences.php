<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_dashboard_preferences')) {
            Schema::create('user_dashboard_preferences', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('default_ticket_filter')->default('all');
                $table->string('default_date_range')->default('30days');
                $table->boolean('show_recommendations')->default(true);
                $table->boolean('show_activity_feed')->default(true);
                $table->boolean('auto_refresh_enabled')->default(true);
                $table->timestamps();

                $table->unique('user_id', 'user_dashboard_preferences_user_id_unique');
                $table->index('user_id', 'user_dashboard_preferences_user_id_index');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        } else {
            $hasUserFk = false;
            $hasDefaultFilter = false;
            $hasDateRange = false;
            $hasRecommendations = false;
            $hasActivityFeed = false;
            $hasAutoRefresh = false;

            $columns = DB::select('PRAGMA table_info(user_dashboard_preferences)');
            foreach ($columns as $col) {
                switch ($col->name) {
                    case 'user_id': $hasUserFk = true; break;
                    case 'default_ticket_filter': $hasDefaultFilter = true; break;
                    case 'default_date_range': $hasDateRange = true; break;
                    case 'show_recommendations': $hasRecommendations = true; break;
                    case 'show_activity_feed': $hasActivityFeed = true; break;
                    case 'auto_refresh_enabled': $hasAutoRefresh = true; break;
                }
            }

            Schema::table('user_dashboard_preferences', function (Blueprint $table) use ($hasUserFk, $hasDefaultFilter, $hasDateRange, $hasRecommendations, $hasActivityFeed, $hasAutoRefresh) {
                if (!$hasUserFk) {
                    $table->uuid('user_id');
                }
                if (!$hasDefaultFilter) {
                    $table->string('default_ticket_filter')->default('all');
                }
                if (!$hasDateRange) {
                    $table->string('default_date_range')->default('30days');
                }
                if (!$hasRecommendations) {
                    $table->boolean('show_recommendations')->default(true);
                }
                if (!$hasActivityFeed) {
                    $table->boolean('show_activity_feed')->default(true);
                }
                if (!$hasAutoRefresh) {
                    $table->boolean('auto_refresh_enabled')->default(true);
                }
            });

            $indexes = DB::select('PRAGMA index_list(user_dashboard_preferences)');
            $hasUniqueUser = false;
            $hasIndexUser = false;
            foreach ($indexes as $idx) {
                if ($idx->name === 'user_dashboard_preferences_user_id_unique') {
                    $hasUniqueUser = true;
                }
                if ($idx->name === 'user_dashboard_preferences_user_id_index') {
                    $hasIndexUser = true;
                }
            }

            if (!$hasUniqueUser) {
                Schema::table('user_dashboard_preferences', function (Blueprint $table) {
                    $table->unique('user_id', 'user_dashboard_preferences_user_id_unique');
                });
            }

            if (!$hasIndexUser) {
                Schema::table('user_dashboard_preferences', function (Blueprint $table) {
                    $table->index('user_id', 'user_dashboard_preferences_user_id_index');
                });
            }

            $fks = DB::select('PRAGMA foreign_key_list(user_dashboard_preferences)');
            $hasUserFkConstraint = false;
            foreach ($fks as $fk) {
                if ($fk->from === 'user_id' && $fk->table === 'users') {
                    $hasUserFkConstraint = true;
                    break;
                }
            }

            if (!$hasUserFkConstraint) {
                Schema::table('user_dashboard_preferences', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                });
            }
        }

        $this->createUpdatedAtTrigger();
    }

    public function down(): void
    {
        $this->dropUpdatedAtTrigger();

        Schema::table('user_dashboard_preferences', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex('user_dashboard_preferences_user_id_index');
            $table->dropUnique('user_dashboard_preferences_user_id_unique');
        });

        Schema::dropIfExists('user_dashboard_preferences');
    }

    private function createUpdatedAtTrigger(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('
                CREATE TRIGGER IF NOT EXISTS update_user_dashboard_preferences_updated_at
                AFTER UPDATE ON user_dashboard_preferences
                FOR EACH ROW
                BEGIN
                    UPDATE user_dashboard_preferences SET updated_at = datetime("now") WHERE id = NEW.id;
                END
            ');
        } elseif ($driver === 'mysql') {
            DB::statement('
                CREATE TRIGGER IF NOT EXISTS update_user_dashboard_preferences_updated_at
                BEFORE UPDATE ON user_dashboard_preferences
                FOR EACH ROW
                BEGIN
                    SET NEW.updated_at = NOW();
                END
            ');
        } elseif ($driver === 'pgsql') {
            DB::statement('
                CREATE OR REPLACE FUNCTION update_user_dashboard_preferences_updated_at()
                RETURNS TRIGGER AS $$
                BEGIN
                    NEW.updated_at = NOW();
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
            ');
            DB::statement('
                CREATE TRIGGER update_user_dashboard_preferences_updated_at
                BEFORE UPDATE ON user_dashboard_preferences
                FOR EACH ROW
                EXECUTE FUNCTION update_user_dashboard_preferences_updated_at();
            ');
        }
    }

    private function dropUpdatedAtTrigger(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS update_user_dashboard_preferences_updated_at');
        } elseif ($driver === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS update_user_dashboard_preferences_updated_at');
        } elseif ($driver === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS update_user_dashboard_preferences_updated_at ON user_dashboard_preferences');
            DB::statement('DROP FUNCTION IF EXISTS update_user_dashboard_preferences_updated_at()');
        }
    }
};

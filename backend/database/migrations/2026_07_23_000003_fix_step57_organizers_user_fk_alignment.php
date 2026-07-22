<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('organizers') || !Schema::hasColumn('organizers', 'userId')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildOrganizersForSqlite();
            return;
        }

        // Backfill Step 57 owner column from the canonical relation where needed.
        DB::table('organizers')
            ->whereNull('userId')
            ->update(['userId' => DB::raw('user_id')]);

        if (! $this->indexExists('organizers', 'organizers_userid_unique')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->unique('userId', 'organizers_userid_unique');
            });
        }

        if (! $this->foreignKeyExists('organizers', 'userId')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->foreign('userId', 'organizers_userid_foreign')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('organizers')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            if ($this->foreignKeyExists('organizers', 'userId')) {
                Schema::table('organizers', function (Blueprint $table) {
                    $table->dropForeign('organizers_userid_foreign');
                });
            }

            if ($this->indexExists('organizers', 'organizers_userid_unique')) {
                Schema::table('organizers', function (Blueprint $table) {
                    $table->dropUnique('organizers_userid_unique');
                });
            }
        }
    }

    private function rebuildOrganizersForSqlite(): void
    {
        DB::beginTransaction();

        try {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('CREATE TABLE organizers_step57_fix (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id TEXT NOT NULL,
                business_name VARCHAR,
                bio TEXT,
                branding_color VARCHAR,
                logo_path VARCHAR,
                website_url VARCHAR,
                social_links TEXT,
                privacy_settings TEXT,
                created_at DATETIME,
                updated_at DATETIME,
                userId VARCHAR,
                displayName VARCHAR,
                avatarUrl VARCHAR,
                email VARCHAR,
                phone VARCHAR,
                website VARCHAR,
                socialLinks TEXT,
                brandingColors TEXT,
                isPublic TINYINT NOT NULL DEFAULT 1,
                emailPublic TINYINT NOT NULL DEFAULT 0,
                phonePublic TINYINT NOT NULL DEFAULT 0,
                notificationPreferences TEXT,
                totalEventsCreated INTEGER NOT NULL DEFAULT 0,
                totalTicketsSold INTEGER NOT NULL DEFAULT 0,
                deletedAt DATETIME,
                UNIQUE(user_id),
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY(userId) REFERENCES users(id) ON DELETE CASCADE
            )');

            DB::statement("INSERT INTO organizers_step57_fix (
                id,
                user_id,
                business_name,
                bio,
                branding_color,
                logo_path,
                website_url,
                social_links,
                privacy_settings,
                created_at,
                updated_at,
                userId,
                displayName,
                avatarUrl,
                email,
                phone,
                website,
                socialLinks,
                brandingColors,
                isPublic,
                emailPublic,
                phonePublic,
                notificationPreferences,
                totalEventsCreated,
                totalTicketsSold,
                deletedAt
            )
            SELECT
                id,
                COALESCE(NULLIF(TRIM(CAST(userId AS TEXT)), ''), CAST(user_id AS TEXT)) AS normalized_user_id,
                business_name,
                bio,
                branding_color,
                logo_path,
                website_url,
                social_links,
                privacy_settings,
                created_at,
                updated_at,
                CASE
                    WHEN COALESCE(NULLIF(TRIM(CAST(userId AS TEXT)), ''), CAST(user_id AS TEXT)) IN (SELECT id FROM users)
                        THEN COALESCE(NULLIF(TRIM(CAST(userId AS TEXT)), ''), CAST(user_id AS TEXT))
                    ELSE NULL
                END AS normalized_userId,
                displayName,
                avatarUrl,
                email,
                phone,
                website,
                socialLinks,
                brandingColors,
                isPublic,
                emailPublic,
                phonePublic,
                notificationPreferences,
                totalEventsCreated,
                totalTicketsSold,
                deletedAt
            FROM organizers");

            DB::statement('DROP TABLE organizers');
            DB::statement('ALTER TABLE organizers_step57_fix RENAME TO organizers');

            DB::statement('CREATE INDEX organizers_userid_index ON organizers(userId)');
            DB::statement('CREATE INDEX organizers_userid_ispublic_index ON organizers(userId, isPublic)');
            DB::statement('CREATE INDEX organizers_created_at_index ON organizers(created_at)');
            DB::statement('CREATE UNIQUE INDEX organizers_userid_unique ON organizers(userId)');

            DB::statement('PRAGMA foreign_keys = ON');
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            DB::statement('PRAGMA foreign_keys = ON');
            throw $e;
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

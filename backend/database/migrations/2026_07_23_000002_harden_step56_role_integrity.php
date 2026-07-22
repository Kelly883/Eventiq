<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->dropInvalidSqliteIndexes();
        }

        $this->syncRolePermissionJsonToPivot();
        $this->addPermissionRequestCompositeIndex();
        $this->canonicalizeUserRoleColumn();
    }

    public function down(): void
    {
        if (Schema::hasTable('permission_requests') && $this->indexExists('permission_requests', 'idx_permission_requests_permission_status')) {
            Schema::table('permission_requests', function (Blueprint $table) {
                $table->dropIndex('idx_permission_requests_permission_status');
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'roles_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->uuid('roles_id')->nullable()->after('role_id');
            });

            if (Schema::hasColumn('users', 'role_id')) {
                DB::statement('UPDATE users SET roles_id = role_id WHERE roles_id IS NULL');
            }
        }
    }

    private function syncRolePermissionJsonToPivot(): void
    {
        if (!Schema::hasTable('roles')
            || !Schema::hasColumn('roles', 'permissions')
            || !Schema::hasTable('permission_role')
            || !Schema::hasTable('permissions')) {
            return;
        }

        $roles = DB::table('roles')->select('id', 'permissions')->whereNotNull('permissions')->get();

        foreach ($roles as $role) {
            $decoded = json_decode((string) $role->permissions, true);
            if (!is_array($decoded)) {
                continue;
            }

            $permissionIds = $this->extractPermissionIds($decoded);
            if (empty($permissionIds)) {
                continue;
            }

            $rows = [];
            foreach ($permissionIds as $permissionId) {
                $rows[] = [
                    'permission_id' => $permissionId,
                    'role_id' => $role->id,
                ];
            }

            DB::table('permission_role')->upsert($rows, ['permission_id', 'role_id'], []);
        }
    }

    /**
     * Supports permission payloads in role JSON as:
     * - [1,2,3]
     * - ["permission.name", ...]
     * - {"permission.name": true, "other": false}
     */
    private function extractPermissionIds(array $decoded): array
    {
        $ids = [];
        $names = [];

        $isList = array_is_list($decoded);

        if ($isList) {
            foreach ($decoded as $value) {
                if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                    $ids[] = (int) $value;
                } elseif (is_string($value) && $value !== '') {
                    $names[] = $value;
                }
            }
        } else {
            foreach ($decoded as $key => $value) {
                if ((bool) $value === true && is_string($key) && $key !== '') {
                    $names[] = $key;
                }
            }
        }

        if (!empty($names)) {
            $nameIds = DB::table('permissions')->whereIn('name', $names)->pluck('id')->all();
            foreach ($nameIds as $id) {
                $ids[] = (int) $id;
            }
        }

        $existing = [];
        if (!empty($ids)) {
            $existing = DB::table('permissions')->whereIn('id', array_unique($ids))->pluck('id')->all();
        }

        return array_values(array_unique(array_map('intval', $existing)));
    }

    private function addPermissionRequestCompositeIndex(): void
    {
        if (!Schema::hasTable('permission_requests')
            || !Schema::hasColumn('permission_requests', 'permissionId')
            || !Schema::hasColumn('permission_requests', 'status')
            || $this->indexExists('permission_requests', 'idx_permission_requests_permission_status')) {
            return;
        }

        Schema::table('permission_requests', function (Blueprint $table) {
            $table->index(['permissionId', 'status'], 'idx_permission_requests_permission_status');
        });
    }

    private function canonicalizeUserRoleColumn(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'roles_id') && Schema::hasColumn('users', 'role_id')) {
            DB::statement('UPDATE users SET role_id = COALESCE(role_id, roles_id)');
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('roles_id');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            return DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $indexName]
            ) !== null;
        }

        return DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        ) !== null;
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

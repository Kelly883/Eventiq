<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->fixRolesTable();
        $this->fixPermissionsTable();
        $this->fixAuditLogsTable();
        $this->fixPermissionRequestsTable();
        $this->fixUsersTable();
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'roles_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('roles_id');
            });
        }

        if (Schema::hasTable('permission_requests') && $this->indexExists('permission_requests', 'permission_requests_permissionid_index')) {
            Schema::table('permission_requests', function (Blueprint $table) {
                $table->dropIndex('permission_requests_permissionid_index');
            });
        }

        if (Schema::hasTable('audit_logs')) {
            if (Schema::hasColumn('audit_logs', 'reason')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropColumn('reason');
                });
            }

            if ($this->indexExists('audit_logs', 'audit_logs_created_at_index')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('audit_logs_created_at_index');
                });
            }

            if ($this->indexExists('audit_logs', 'audit_logs_adminid_index')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('audit_logs_adminid_index');
                });
            }

            if ($this->indexExists('audit_logs', 'audit_logs_targetuserid_index')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('audit_logs_targetuserid_index');
                });
            }
        }
    }

    private function fixRolesTable(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        if (!$this->indexExists('roles', 'roles_issystemrole_index') && Schema::hasColumn('roles', 'isSystemRole')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->index('isSystemRole');
            });
        }
    }

    private function fixPermissionsTable(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        if (!Schema::hasColumn('permissions', 'category')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('category')->nullable()->after('description');
            });
        }

        if (!Schema::hasColumn('permissions', 'riskLevel')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('riskLevel')->nullable()->after('category');
            });
        }

        if (!$this->indexExists('permissions', 'permissions_category_index') && Schema::hasColumn('permissions', 'category')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->index('category');
            });
        }
    }

    private function fixAuditLogsTable(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        if (!Schema::hasColumn('audit_logs', 'reason')) {
            if (Schema::hasColumn('audit_logs', 'changes')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->text('reason')->nullable()->after('changes');
                });
            } else {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->text('reason')->nullable();
                });
            }
        }

        if (!$this->indexExists('audit_logs', 'audit_logs_adminid_index') && Schema::hasColumn('audit_logs', 'adminId')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('adminId');
            });
        }

        if (!$this->indexExists('audit_logs', 'audit_logs_targetuserid_index') && Schema::hasColumn('audit_logs', 'targetUserId')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('targetUserId');
            });
        }

        if (!$this->indexExists('audit_logs', 'audit_logs_created_at_index') && Schema::hasColumn('audit_logs', 'created_at')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('created_at');
            });
        }

        if (!$this->foreignKeyExists('audit_logs', 'adminId') && Schema::hasColumn('audit_logs', 'adminId')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreign('adminId')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!$this->foreignKeyExists('audit_logs', 'targetUserId') && Schema::hasColumn('audit_logs', 'targetUserId')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreign('targetUserId')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    private function fixPermissionRequestsTable(): void
    {
        if (!Schema::hasTable('permission_requests')) {
            return;
        }

        if (!$this->indexExists('permission_requests', 'permission_requests_userid_index') && Schema::hasColumn('permission_requests', 'userId')) {
            Schema::table('permission_requests', function (Blueprint $table) {
                $table->index('userId');
            });
        }

        if (!$this->indexExists('permission_requests', 'permission_requests_status_index') && Schema::hasColumn('permission_requests', 'status')) {
            Schema::table('permission_requests', function (Blueprint $table) {
                $table->index('status');
            });
        }

        if (!$this->indexExists('permission_requests', 'permission_requests_permissionid_index') && Schema::hasColumn('permission_requests', 'permissionId')) {
            Schema::table('permission_requests', function (Blueprint $table) {
                $table->index('permissionId');
            });
        }
    }

    private function fixUsersTable(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('permissions')->nullable();
            });
        }

        // Backward-compatibility alias for specs expecting roles_id while existing schema uses role_id.
        if (!Schema::hasColumn('users', 'roles_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->uuid('roles_id')->nullable()->after('role_id');
            });

            if (Schema::hasColumn('users', 'role_id')) {
                DB::statement('UPDATE users SET roles_id = role_id WHERE roles_id IS NULL');
            }
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

    private function foreignKeyExists(string $table, string $column): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            $rows = DB::select("PRAGMA foreign_key_list('{$table}')");
            foreach ($rows as $row) {
                if (($row->from ?? null) === $column) {
                    return true;
                }
            }

            return false;
        }

        return DB::selectOne(
            'SELECT column_name FROM information_schema.key_column_usage WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? AND referenced_table_name IS NOT NULL',
            [$table, $column]
        ) !== null;
    }
};

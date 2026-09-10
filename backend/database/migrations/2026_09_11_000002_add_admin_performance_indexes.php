<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Users: for admin search/filter (email LIKE, name LIKE, role lookups)
        // Note: LIKE '%search%' cannot use B-tree index efficiently, but prefix searches and
        // ordering benefit from indexes. Also add for audit forensics.
        Schema::table('users', function (Blueprint $table) {
            // Check if index already exists to avoid duplicate
            $this->safeAddIndex($table, 'users', 'email', 'idx_users_email_search');
            $this->safeAddIndex($table, 'users', 'name', 'idx_users_name_search');
            // Composite for role filters (legacy string column + created_at ordering)
            $this->safeAddIndex($table, 'users', ['role', 'created_at'], 'idx_users_role_created');
        });

        // Audit logs: hot queries are target_type+target_id+action+created_at
        // and user_id filtering. Add composite indexes if not present.
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $this->safeAddIndex($table, 'audit_logs', ['target_type', 'target_id'], 'idx_audit_target');
                $this->safeAddIndex($table, 'audit_logs', ['target_type', 'target_id', 'action'], 'idx_audit_target_action');
                $this->safeAddIndex($table, 'audit_logs', 'created_at', 'idx_audit_created_at');
                $this->safeAddIndex($table, 'audit_logs', ['user_id', 'created_at'], 'idx_audit_user_created');
                $this->safeAddIndex($table, 'audit_logs', 'action', 'idx_audit_action');
            });
        }

        // Sessions: for invalidation checks (userId + revokedAt + expiresAt)
        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $this->safeAddIndex($table, 'sessions', ['userId', 'revokedAt'], 'idx_sessions_user_revoked');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $this->safeDropIndex($table, 'idx_users_email_search');
            $this->safeDropIndex($table, 'idx_users_name_search');
            $this->safeDropIndex($table, 'idx_users_role_created');
        });

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $this->safeDropIndex($table, 'idx_audit_target');
                $this->safeDropIndex($table, 'idx_audit_target_action');
                $this->safeDropIndex($table, 'idx_audit_created_at');
                $this->safeDropIndex($table, 'idx_audit_user_created');
                $this->safeDropIndex($table, 'idx_audit_action');
            });
        }

        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $this->safeDropIndex($table, 'idx_sessions_user_revoked');
            });
        }
    }

    private function safeAddIndex(Blueprint $table, string $tableName, $columns, string $indexName): void
    {
        try {
            // Check via sqlite_master or information_schema for existing index
            $exists = false;
            try {
                $rows = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=? AND name=?", [$tableName, $indexName]);
                $exists = !empty($rows);
            } catch (\Throwable $e) {
                // Not sqlite, try postgres/mysql via Doctrine
                try {
                    $sm = DB::connection()->getDoctrineSchemaManager();
                    if ($sm->tablesExist([$tableName])) {
                        $indexes = $sm->listTableIndexes($tableName);
                        $exists = isset($indexes[strtolower($indexName)]) || isset($indexes[$indexName]);
                    }
                } catch (\Throwable $e2) {
                    // Fallback: try to add and catch duplicate
                }
            }
            if (!$exists) {
                $table->index($columns, $indexName);
            }
        } catch (\Throwable $e) {
            // Index already exists or not supported — ignore
            if (!str_contains($e->getMessage(), 'already exists') && !str_contains($e->getMessage(), 'duplicate')) {
                throw $e;
            }
        }
    }

    private function safeDropIndex(Blueprint $table, string $indexName): void
    {
        try {
            $table->dropIndex($indexName);
        } catch (\Throwable $e) {
            // Ignore if not exists
        }
    }
};

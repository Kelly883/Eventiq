<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users: for admin search/filter (email LIKE, name LIKE, role lookups)
        // Note: LIKE '%search%' cannot use B-tree index efficiently, but prefix searches and
        // ordering benefit from indexes. Also add for audit forensics.
        //
        // NOTE: users.email already has a unique index from the users table creation migration
        // (2026_07_04_000000_create_users_table.php line 14: $table->string('email')->unique()->index()).
        // The idx_users_email_search index may be redundant for search purposes since the unique
        // index can also be used for lookups. However, this migration adds a explicitly named index
        // for clarity and admin search tooling. If redundancy is a concern, consider removing this
        // index and relying on the existing unique index.
        if (!Schema::hasIndex('users', 'idx_users_email_search')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('email', 'idx_users_email_search');
            });
        }

        if (!Schema::hasIndex('users', 'idx_users_name_search')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('name', 'idx_users_name_search');
            });
        }

        // Composite for role filters (legacy string column + created_at ordering)
        if (!Schema::hasIndex('users', 'idx_users_role_created')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['role', 'created_at'], 'idx_users_role_created');
            });
        }

        // Audit logs: hot queries are target_type+target_id+action+created_at
        // and user_id filtering. Add composite indexes if not present.
        if (Schema::hasTable('audit_logs')) {
            if (!Schema::hasIndex('audit_logs', 'idx_audit_target')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index(['target_type', 'target_id'], 'idx_audit_target');
                });
            }

            if (!Schema::hasIndex('audit_logs', 'idx_audit_target_action')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index(['target_type', 'target_id', 'action'], 'idx_audit_target_action');
                });
            }

            if (!Schema::hasIndex('audit_logs', 'idx_audit_created_at')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index('created_at', 'idx_audit_created_at');
                });
            }

            if (!Schema::hasIndex('audit_logs', 'idx_audit_user_created')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at'], 'idx_audit_user_created');
                });
            }

            if (!Schema::hasIndex('audit_logs', 'idx_audit_action')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index('action', 'idx_audit_action');
                });
            }
        }

        // Sessions: for invalidation checks (userId + revokedAt + expiresAt)
        if (Schema::hasTable('sessions')) {
            if (!Schema::hasIndex('sessions', 'idx_sessions_user_revoked')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->index(['userId', 'revokedAt'], 'idx_sessions_user_revoked');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('users', 'idx_users_email_search')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_email_search');
            });
        }

        if (Schema::hasIndex('users', 'idx_users_name_search')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_name_search');
            });
        }

        if (Schema::hasIndex('users', 'idx_users_role_created')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_role_created');
            });
        }

        if (Schema::hasTable('audit_logs')) {
            if (Schema::hasIndex('audit_logs', 'idx_audit_target')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('idx_audit_target');
                });
            }

            if (Schema::hasIndex('audit_logs', 'idx_audit_target_action')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('idx_audit_target_action');
                });
            }

            if (Schema::hasIndex('audit_logs', 'idx_audit_created_at')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('idx_audit_created_at');
                });
            }

            if (Schema::hasIndex('audit_logs', 'idx_audit_user_created')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('idx_audit_user_created');
                });
            }

            if (Schema::hasIndex('audit_logs', 'idx_audit_action')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('idx_audit_action');
                });
            }
        }

        if (Schema::hasTable('sessions')) {
            if (Schema::hasIndex('sessions', 'idx_sessions_user_revoked')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->dropIndex('idx_sessions_user_revoked');
                });
            }
        }
    }
};

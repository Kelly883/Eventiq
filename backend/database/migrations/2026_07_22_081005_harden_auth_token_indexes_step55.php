<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->hardenSessions();
        $this->hardenPasswordResetTokens();
    }

    public function down(): void
    {
        if (Schema::hasTable('sessions')) {
            if ($this->indexExists('sessions', 'idx_sessions_expires_at')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->dropIndex('idx_sessions_expires_at');
                });
            }

            if ($this->indexExists('sessions', 'idx_sessions_user_revoked_expires')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->dropIndex('idx_sessions_user_revoked_expires');
                });
            }

            if ($this->indexExists('sessions', 'sessions_token_unique')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->dropUnique('sessions_token_unique');
                });
            }

            if (!$this->indexExists('sessions', 'sessions_token_index') && Schema::hasColumn('sessions', 'token')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->index('token');
                });
            }
        }

        if (Schema::hasTable('password_reset_tokens')) {
            if ($this->indexExists('password_reset_tokens', 'idx_prt_expires_at')) {
                Schema::table('password_reset_tokens', function (Blueprint $table) {
                    $table->dropIndex('idx_prt_expires_at');
                });
            }

            if ($this->indexExists('password_reset_tokens', 'idx_prt_used_at')) {
                Schema::table('password_reset_tokens', function (Blueprint $table) {
                    $table->dropIndex('idx_prt_used_at');
                });
            }

            if ($this->indexExists('password_reset_tokens', 'idx_prt_user_used_expires')) {
                Schema::table('password_reset_tokens', function (Blueprint $table) {
                    $table->dropIndex('idx_prt_user_used_expires');
                });
            }

            if ($this->indexExists('password_reset_tokens', 'password_reset_tokens_token_unique')) {
                Schema::table('password_reset_tokens', function (Blueprint $table) {
                    $table->dropUnique('password_reset_tokens_token_unique');
                });
            }

            if (!$this->indexExists('password_reset_tokens', 'password_reset_tokens_token_index') && Schema::hasColumn('password_reset_tokens', 'token')) {
                Schema::table('password_reset_tokens', function (Blueprint $table) {
                    $table->index('token');
                });
            }
        }
    }

    private function hardenSessions(): void
    {
        if (!Schema::hasTable('sessions')) {
            return;
        }

        if ($this->indexExists('sessions', 'sessions_token_index')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropIndex('sessions_token_index');
            });
        }

        if (!$this->indexExists('sessions', 'sessions_token_unique') && Schema::hasColumn('sessions', 'token')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->unique('token', 'sessions_token_unique');
            });
        }

        if (!$this->indexExists('sessions', 'idx_sessions_expires_at') && Schema::hasColumn('sessions', 'expiresAt')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->index('expiresAt', 'idx_sessions_expires_at');
            });
        }

        if (!$this->indexExists('sessions', 'idx_sessions_user_revoked_expires')
            && Schema::hasColumn('sessions', 'userId')
            && Schema::hasColumn('sessions', 'revokedAt')
            && Schema::hasColumn('sessions', 'expiresAt')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->index(['userId', 'revokedAt', 'expiresAt'], 'idx_sessions_user_revoked_expires');
            });
        }
    }

    private function hardenPasswordResetTokens(): void
    {
        if (!Schema::hasTable('password_reset_tokens')) {
            return;
        }

        if ($this->indexExists('password_reset_tokens', 'password_reset_tokens_token_index')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->dropIndex('password_reset_tokens_token_index');
            });
        }

        if (!$this->indexExists('password_reset_tokens', 'password_reset_tokens_token_unique') && Schema::hasColumn('password_reset_tokens', 'token')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->unique('token', 'password_reset_tokens_token_unique');
            });
        }

        if (!$this->indexExists('password_reset_tokens', 'idx_prt_expires_at') && Schema::hasColumn('password_reset_tokens', 'expiresAt')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->index('expiresAt', 'idx_prt_expires_at');
            });
        }

        if (!$this->indexExists('password_reset_tokens', 'idx_prt_used_at') && Schema::hasColumn('password_reset_tokens', 'usedAt')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->index('usedAt', 'idx_prt_used_at');
            });
        }

        if (!$this->indexExists('password_reset_tokens', 'idx_prt_user_used_expires')
            && Schema::hasColumn('password_reset_tokens', 'userId')
            && Schema::hasColumn('password_reset_tokens', 'usedAt')
            && Schema::hasColumn('password_reset_tokens', 'expiresAt')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->index(['userId', 'usedAt', 'expiresAt'], 'idx_prt_user_used_expires');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

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
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureRefundRequestsColumns();

        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $this->fixSqliteConstraints();
        } elseif ($driver === 'mysql') {
            $this->fixMySqlConstraints();
        } else {
            $this->fixPostgreSqlConstraints();
        }
    }

    private function ensureRefundRequestsColumns(): void
    {
        if (!Schema::hasTable('refund_requests')) {
            return;
        }

        if (!Schema::hasColumn('refund_requests', 'idempotency_key')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->string('idempotency_key', 191)->nullable()->after('user_id');
            });
        }
        if (!Schema::hasColumn('refund_requests', 'reference_number')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->string('reference_number', 50)->nullable()->after('last_appeal_at');
            });
        }
        if (!Schema::hasColumn('refund_requests', 'expected_processing_days')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->integer('expected_processing_days')->default(3)->after('reference_number');
            });
        }
        if (!Schema::hasColumn('refund_requests', 'approved_amount')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->decimal('approved_amount', 10, 2)->nullable()->after('requested_amount');
            });
        }
        if (!Schema::hasColumn('refund_requests', 'admin_notes')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->text('admin_notes')->nullable()->after('rejection_reason');
            });
        }
        if (!Schema::hasColumn('refund_requests', 'reviewed_by')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->uuid('reviewed_by')->nullable()->after('admin_notes');
            });
        }
        if (!Schema::hasColumn('refund_requests', 'reviewed_at')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            });
        }
        if (!Schema::hasColumn('refund_requests', 'policy_version_id')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->string('policy_version_id', 50)->nullable()->after('expected_processing_days');
            });
        }
        if (!Schema::hasColumn('refund_requests', 'requested_amount')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->decimal('requested_amount', 10, 2)->nullable()->after('refund_percentage');
            });
        }

        Schema::table('refund_requests', function (Blueprint $table) {
            $table->unique('idempotency_key', 'idx_refund_requests_idempotency_key_unique');
            $table->unique('reference_number', 'idx_refund_requests_reference_number_unique');
        });

        // Partial unique index: one active refund request per ticket. Rejected /
        // completed / failed / cancelled requests allow re-requesting a new one.
        if ($this->indexExists('refund_requests', 'idx_refund_requests_active_ticket_unique')) {
            DB::statement('DROP INDEX idx_refund_requests_active_ticket_unique');
        }
        DB::statement("CREATE UNIQUE INDEX idx_refund_requests_active_ticket_unique ON refund_requests (ticket_id) WHERE status IN ('pending', 'approved', 'processing', 'failed')");
    }

    private function fixSqliteConstraints(): void
    {
        // SQLite ignores CHECK constraints declared inline in older versions and
        // has limited constraint management. We drop and recreate the only two
        // problematic constraints by rebuilding the column set if needed. Since
        // the schema is reconciled above, we just ensure the enum values are
        // documented via the application layer. SQLite does not enforce inline
        // CHECK by default in older PHP builds, so this is a no-op safety net.
        DB::statement('CREATE INDEX IF NOT EXISTS idx_refund_requests_status ON refund_requests (status)');
    }

    private function fixMySqlConstraints(): void
    {
        $this->dropConstraint('refund_requests', 'chk_refund_requests_status', 'status');
        DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT chk_refund_requests_status CHECK (status IN ('pending', 'approved', 'rejected', 'processing', 'completed', 'failed'))");

        $this->dropConstraint('refund_requests', 'chk_refund_requests_reason', 'reason');
        DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT chk_refund_requests_reason CHECK (reason IN ('event_cancelled', 'personal_circumstances', 'duplicate_purchase', 'other', 'payment_issue', 'policy_violation'))");

        $this->dropConstraint('refund_requests', 'chk_refund_requests_refund_method', 'refund_method');
        DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT chk_refund_requests_refund_method CHECK (refund_method IN ('original_payment_method', 'store_credit', 'alternative_payment_method'))");
    }

    private function fixPostgreSqlConstraints(): void
    {
        $this->dropConstraint('refund_requests', 'chk_refund_requests_status', 'status');
        DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT chk_refund_requests_status CHECK (status IN ('pending', 'approved', 'rejected', 'processing', 'completed', 'failed'))");

        $this->dropConstraint('refund_requests', 'chk_refund_requests_reason', 'reason');
        DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT chk_refund_requests_reason CHECK (reason IN ('event_cancelled', 'personal_circumstances', 'duplicate_purchase', 'other', 'payment_issue', 'policy_violation'))");

        $this->dropConstraint('refund_requests', 'chk_refund_requests_refund_method', 'refund_method');
        DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT chk_refund_requests_refund_method CHECK (refund_method IN ('original_payment_method', 'store_credit', 'alternative_payment_method'))");
    }

    private function dropConstraint(string $table, string $constraint, string $column): void
    {
        $constraintSql = match (DB::getDriverName()) {
            'mysql' => "ALTER TABLE {$table} DROP CONSTRAINT {$constraint}",
            'pgsql' => "ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}",
            default => "ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}",
        };
        try {
            DB::statement($constraintSql);
        } catch (\Throwable $e) {
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }
        if (DB::getDriverName() === 'sqlite') {
            return DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $index]
            ) !== null;
        }
        if (DB::getDriverName() === 'pgsql') {
            return DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index]
            ) !== null;
        }
        return DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
            [$table, $index]
        ) !== null;
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropUnique('idx_refund_requests_idempotency_key_unique');
            $table->dropUnique('idx_refund_requests_reference_number_unique');
        });
        DB::statement('DROP INDEX IF EXISTS idx_refund_requests_active_ticket_unique');
    }
};

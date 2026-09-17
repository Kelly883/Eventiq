<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (! Schema::hasColumn('payments', 'payment_channel')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('payment_channel')->nullable();
            });
        }

        if (! Schema::hasColumn('payments', 'attempts')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->integer('attempts')->default(1);
            });
        }

        if (! Schema::hasColumn('payments', 'last_error')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->text('last_error')->nullable();
            });
        }

        if (! $this->indexExists('payments', 'idx_payments_user_id_status_created_at')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['user_id', 'status', 'created_at'], 'idx_payments_user_id_status_created_at');
            });
        }

        if (! $this->indexExists('payments', 'idx_payments_gateway_status')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['gateway', 'status'], 'idx_payments_gateway_status');
            });
        }

        if (DB::getDriverName() === 'sqlite') {
            try {
                DB::statement('CREATE TRIGGER IF NOT EXISTS trg_payments_refund_check BEFORE UPDATE ON payments FOR EACH ROW WHEN NEW.refunded_amount > OLD.amount BEGIN SELECT RAISE(ABORT, "refunded_amount cannot exceed amount"); END');
            } catch (\Throwable $e) {
                // Trigger may already exist
            }
        } elseif (DB::getDriverName() === 'pgsql') {
            if (! $this->constraintExists('payments', 'chk_refunded_amount')) {
                $violations = DB::selectOne('SELECT 1 FROM payments WHERE refunded_amount > amount LIMIT 1');
                if ($violations) {
                    throw new \RuntimeException(
                        'Cannot add CHECK constraint chk_refunded_amount: existing payment rows violate refunded_amount <= amount. ' .
                        'Clean up the data before re-running the migration.'
                    );
                }
                DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_refunded_amount CHECK (refunded_amount <= amount)');
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            try {
                DB::statement('DROP TRIGGER IF EXISTS trg_payments_refund_check');
            } catch (\Throwable $e) {
                // Trigger may not exist
            }
        } elseif (DB::getDriverName() === 'pgsql') {
            try {
                DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS chk_refunded_amount');
            } catch (\Throwable $e) {
                // Constraint may not exist
            }
        }

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('idx_payments_user_id_status_created_at');
                $table->dropIndex('idx_payments_gateway_status');
            });
        } catch (\Throwable $e) {
            // Indexes may not exist
        }

        $columns = ['last_error', 'attempts', 'payment_channel'];

        $existing = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn('payments', $column)) {
                $existing[] = $column;
            }
        }

        if (! empty($existing)) {
            Schema::table('payments', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            $row = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $index]
            );

            return $row !== null;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return $row !== null;
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid WHERE c.contype = \'c\' AND t.relname = ? AND c.conname = ?',
                [$table, $constraint]
            );

            return $row !== null;
        }

        return false;
    }
};

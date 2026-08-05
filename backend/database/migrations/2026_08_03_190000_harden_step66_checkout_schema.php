<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeOrdersTotalAmountType();
        $this->hardenPaymentIntentConstraints();
        $this->addQueryIndexes();
        $this->createOrderDeleteTicketVoidTrigger();
    }

    public function down(): void
    {
        $this->dropOrderDeleteTicketVoidTrigger();

        if (Schema::hasTable('orders')) {
            if ($this->indexExists('orders', 'idx_orders_user_created_at')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropIndex('idx_orders_user_created_at');
                });
            }

            if ($this->indexExists('orders', 'idx_orders_event_created_at')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropIndex('idx_orders_event_created_at');
                });
            }
        }

        if (Schema::hasTable('tickets') && $this->indexExists('tickets', 'idx_tickets_event_created_at')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropIndex('idx_tickets_event_created_at');
            });
        }

        if (Schema::hasTable('payments')) {
            if ($this->indexExists('payments', 'idx_payments_order_status_created_at')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropIndex('idx_payments_order_status_created_at');
                });
            }

            if ($this->indexExists('payments', 'idx_payments_payment_intent_id')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropIndex('idx_payments_payment_intent_id');
                });
            }

            if ($this->indexExists('payments', 'idx_payments_gateway_payment_intent_id')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropIndex('idx_payments_gateway_payment_intent_id');
                });
            }

            if ($this->indexExists('payments', 'uq_payments_gateway_payment_intent_id')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropUnique('uq_payments_gateway_payment_intent_id');
                });
            }
        }
    }

    private function normalizeOrdersTotalAmountType(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'total_amount')) {
            return;
        }

        $type = $this->getColumnType('orders', 'total_amount');
        if ($type === null || $this->isDecimalLikeType($type)) {
            return;
        }

        $driver = DB::getDriverName();

        // SQLite cannot reliably alter declared column types in-place without table rebuild.
        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `orders` MODIFY `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE orders ALTER COLUMN total_amount TYPE NUMERIC(10,2) USING total_amount::numeric');
            DB::statement("ALTER TABLE orders ALTER COLUMN total_amount SET DEFAULT '0'");
        }
    }

    private function hardenPaymentIntentConstraints(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        if (Schema::hasColumn('payments', 'payment_intent_id') && !$this->indexExists('payments', 'idx_payments_payment_intent_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('payment_intent_id', 'idx_payments_payment_intent_id');
            });
        }

        if (!Schema::hasColumn('payments', 'gateway') || !Schema::hasColumn('payments', 'payment_intent_id')) {
            return;
        }

        $duplicateCount = (int) DB::table('payments')
            ->select('gateway', 'payment_intent_id', DB::raw('COUNT(*) AS occurrences'))
            ->whereNotNull('payment_intent_id')
            ->groupBy('gateway', 'payment_intent_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicateCount === 0) {
            if (!$this->indexExists('payments', 'uq_payments_gateway_payment_intent_id')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->unique(['gateway', 'payment_intent_id'], 'uq_payments_gateway_payment_intent_id');
                });
            }

            return;
        }

        if (!$this->indexExists('payments', 'idx_payments_gateway_payment_intent_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['gateway', 'payment_intent_id'], 'idx_payments_gateway_payment_intent_id');
            });
        }
    }

    private function addQueryIndexes(): void
    {
        if (Schema::hasTable('orders')) {
            if (!$this->indexExists('orders', 'idx_orders_user_created_at')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at'], 'idx_orders_user_created_at');
                });
            }

            if (!$this->indexExists('orders', 'idx_orders_event_created_at')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index(['event_id', 'created_at'], 'idx_orders_event_created_at');
                });
            }
        }

        if (Schema::hasTable('tickets') && !$this->indexExists('tickets', 'idx_tickets_event_created_at')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->index(['event_id', 'created_at'], 'idx_tickets_event_created_at');
            });
        }

        if (Schema::hasTable('payments') && !$this->indexExists('payments', 'idx_payments_order_status_created_at')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['order_id', 'status', 'created_at'], 'idx_payments_order_status_created_at');
            });
        }
    }

    private function createOrderDeleteTicketVoidTrigger(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('tickets')) {
            return;
        }

        if (!Schema::hasColumn('tickets', 'order_id') || !Schema::hasColumn('tickets', 'status')) {
            return;
        }

        $this->dropOrderDeleteTicketVoidTrigger();

        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(
                'CREATE TRIGGER trg_orders_before_delete_void_tickets
                BEFORE DELETE ON orders
                FOR EACH ROW
                BEGIN
                    UPDATE tickets
                    SET status = CASE WHEN status = "valid" THEN "void" ELSE status END,
                        updated_at = COALESCE(updated_at, CURRENT_TIMESTAMP)
                    WHERE order_id = OLD.id;
                END;'
            );

            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::unprepared(
                'CREATE TRIGGER trg_orders_before_delete_void_tickets
                BEFORE DELETE ON orders
                FOR EACH ROW
                UPDATE tickets
                SET status = CASE WHEN status = "valid" THEN "void" ELSE status END,
                    updated_at = IFNULL(updated_at, NOW())
                WHERE order_id = OLD.id'
            );
        }
    }

    private function dropOrderDeleteTicketVoidTrigger(): void
    {
        try {
            DB::statement('DROP TRIGGER IF EXISTS trg_orders_before_delete_void_tickets');
        } catch (\Throwable $e) {
            // No-op: trigger may not exist on the active connection.
        }
    }

    private function getColumnType(string $table, string $column): ?string
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA table_info($table)");
            foreach ($rows as $row) {
                if (($row->name ?? null) === $column) {
                    return strtolower((string) ($row->type ?? ''));
                }
            }

            return null;
        }

        if ($driver === 'mysql') {
            $row = DB::selectOne(
                'SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
                [$table, $column]
            );

            return $row ? strtolower((string) $row->COLUMN_TYPE) : null;
        }

        if ($driver === 'pgsql') {
            $row = DB::selectOne(
                'SELECT data_type FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?',
                [$table, $column]
            );

            return $row ? strtolower((string) $row->data_type) : null;
        }

        return null;
    }

    private function isDecimalLikeType(string $type): bool
    {
        foreach (['decimal', 'numeric', 'real', 'double', 'float'] as $needle) {
            if (str_contains($type, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $row = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=? AND name=?",
                [$table, $indexName]
            );

            return $row !== null;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ?',
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

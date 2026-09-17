<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing foreign key constraints and useful fraud analysis columns.
     *
     * 1. Foreign keys: order_id, user_id, reviewed_by, escalated_to
     * 2. Denormalized columns: order_total, ticket_quantity
     * 3. Billing verification columns: billing_country, billing_zip, shipping_billing_match
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->addColumnsForSqlite();
        } else {
            $this->addColumnsAndForeignKeys();
        }
    }

    private function addColumnsAndForeignKeys(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            // === Add missing foreign key constraints ===
            if (! $this->foreignKeyExists('fraud_events', 'order_id')) {
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            }

            if (! $this->foreignKeyExists('fraud_events', 'user_id')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }

            if (! $this->foreignKeyExists('fraud_events', 'reviewed_by')) {
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            }

            if (! $this->foreignKeyExists('fraud_events', 'escalated_to')) {
                $table->foreign('escalated_to')->references('id')->on('users')->onDelete('set null');
            }

            $this->addAnalysisColumns($table);
            $this->addIndexes($table);
        });
    }

    private function addColumnsForSqlite(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            $this->addAnalysisColumns($table);
        });

        Schema::table('fraud_events', function (Blueprint $table) {
            $this->addIndexes($table);
        });
    }

    private function addAnalysisColumns(Blueprint $table): void
    {
        if (!Schema::hasColumn('fraud_events', 'order_total')) {
            $table->decimal('order_total', 12, 2)->nullable()->after('amount');
        }

        if (!Schema::hasColumn('fraud_events', 'ticket_quantity')) {
            $table->integer('ticket_quantity')->nullable()->after('order_total');
        }

        if (!Schema::hasColumn('fraud_events', 'billing_country')) {
            $table->string('billing_country', 2)->nullable()->after('ticket_quantity');
        }

        if (!Schema::hasColumn('fraud_events', 'billing_zip')) {
            $table->string('billing_zip', 20)->nullable()->after('billing_country');
        }

        if (!Schema::hasColumn('fraud_events', 'shipping_billing_match')) {
            $table->boolean('shipping_billing_match')->nullable()->after('billing_zip');
        }
    }

    private function addIndexes(Blueprint $table): void
    {
        if (Schema::hasColumn('fraud_events', 'order_total')) {
            $table->index('order_total', 'idx_fraud_order_total');
        }

        if (Schema::hasColumn('fraud_events', 'billing_country')) {
            $table->index('billing_country', 'idx_fraud_billing_country');
        }

        if (Schema::hasColumn('fraud_events', 'ticket_quantity')) {
            $table->index('ticket_quantity', 'idx_fraud_ticket_quantity');
        }
    }

    public function down(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            $table->dropIndex('idx_fraud_order_total');
            $table->dropIndex('idx_fraud_billing_country');
            $table->dropIndex('idx_fraud_ticket_quantity');

            $table->dropColumn([
                'order_total',
                'ticket_quantity',
                'billing_country',
                'billing_zip',
                'shipping_billing_match',
            ]);
        });
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

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT c.conname FROM pg_constraint c '
                . 'JOIN pg_class t ON c.conrelid = t.oid '
                . 'JOIN pg_namespace n ON t.relnamespace = n.oid '
                . "WHERE n.nspname = current_schema() "
                . "AND t.relname = ? "
                . "AND c.contype = 'f' "
                . 'AND EXISTS ('
                . '  SELECT 1 FROM pg_attribute a '
                . '  WHERE a.attrelid = t.oid '
                . '  AND a.attname = ? '
                . '  AND a.attnum = ANY(c.conkey)'
                . ')',
                [$table, $column]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT column_name FROM information_schema.key_column_usage WHERE table_schema = current_schema() AND table_name = ? AND column_name = ? AND referenced_table_name IS NOT NULL',
            [$table, $column]
        );

        return $row !== null;
    }
};
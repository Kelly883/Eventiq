<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Addresses all remaining issues identified in Step 66 verification:
     * 1. Add user_id index on tickets table (for "My Tickets" queries)
     * 2. Add event_id index on orders table (for admin analytics)
     * 3. Add fees, net_amount columns on payments (for reconciliation)
     * 4. Add refunded_at timestamp on payments (missing from previous migration)
     * 5. Add refund_reason text on payments (for full audit trail)
     * 6. Add card_last_four, card_brand on payments (for UX)
     * 7. Add failure_reason on orders (for debugging failed payments)
     * 8. Composite index user_id + status on orders (user-facing order list)
     */
    public function up(): void
    {
        // ============ ORDERS TABLE ============
        if (Schema::hasTable('orders')) {
            // 2. event_id index for "all orders for event X" admin queries
            if (! $this->indexExists('orders', 'idx_orders_event_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index('event_id', 'idx_orders_event_id');
                });
            }

            // 8. Composite user_id + status for user-facing "my orders" queries
            if (! $this->indexExists('orders', 'idx_orders_user_status')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index(['user_id', 'status'], 'idx_orders_user_status');
                });
            }

            // 7. failure_reason for debugging failed payments
            if (! Schema::hasColumn('orders', 'failure_reason')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->text('failure_reason')->nullable()->after('status');
                });
            }
        }

        // ============ TICKETS TABLE ============
        if (Schema::hasTable('tickets')) {
            // 1. user_id index for "my tickets" queries
            if (! $this->indexExists('tickets', 'idx_tickets_user_id')) {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->index('user_id', 'idx_tickets_user_id');
                });
            }

            if (! $this->indexExists('tickets', 'idx_tickets_order_id')) {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->index('order_id', 'idx_tickets_order_id');
                });
            }
        }

        // ============ PAYMENTS TABLE ============
        if (Schema::hasTable('payments')) {
            // 4. refunded_at timestamp (referenced but missing in previous migration)
            if (! Schema::hasColumn('payments', 'refunded_at')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->timestamp('refunded_at')->nullable()->after('refunded_by');
                });
            }

            // 3. fees and net_amount for financial reconciliation
            if (! Schema::hasColumn('payments', 'fees')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->decimal('fees', 10, 2)->nullable()->after('amount');
                    $table->decimal('net_amount', 10, 2)->nullable()->after('fees');
                });
            }

            // 5. refund_reason for audit trail
            if (! Schema::hasColumn('payments', 'refund_reason')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->text('refund_reason')->nullable()->after('refunded_at');
                });
            }

            // 6. Card info for payment method display
            if (! Schema::hasColumn('payments', 'card_last_four')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('card_last_four', 4)->nullable()->after('gateway_response');
                    $table->string('card_brand', 50)->nullable()->after('card_last_four');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            $hasOrdersFailureReason = Schema::hasColumn('orders', 'failure_reason');
            Schema::table('orders', function (Blueprint $table) use ($hasOrdersFailureReason) {
                try { $table->dropIndex('idx_orders_event_id'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_orders_user_status'); } catch (\Exception $e) {}
                if ($hasOrdersFailureReason) {
                    $table->dropColumn('failure_reason');
                }
            });
        }

        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                try { $table->dropIndex('idx_tickets_user_id'); } catch (\Exception $e) {}
                try { $table->dropIndex('idx_tickets_order_id'); } catch (\Exception $e) {}
            });
        }

        if (Schema::hasTable('payments')) {
            $columns = ['refunded_at', 'fees', 'net_amount', 'refund_reason',
                'card_last_four', 'card_brand'];
            $existing = [];
            foreach ($columns as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $existing[] = $col;
                }
            }
            if (! empty($existing)) {
                Schema::table('payments', function (Blueprint $table) use ($existing) {
                    $table->dropColumn($existing);
                });
            }
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

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT i.relname FROM pg_index x '
                . 'JOIN pg_class i ON x.indexrelid = i.oid '
                . 'JOIN pg_class t ON x.indrelid = t.oid '
                . 'JOIN pg_namespace n ON t.relnamespace = n.oid '
                . 'WHERE n.nspname = current_schema() '
                . 'AND t.relname = ? '
                . 'AND i.relname = ?',
                [$table, $indexName]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );

        return $row !== null;
    }
};

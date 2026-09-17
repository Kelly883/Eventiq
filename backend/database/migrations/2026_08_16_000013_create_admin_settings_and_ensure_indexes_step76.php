<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('admin_settings')) {
            Schema::create('admin_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('setting_key')->unique();
                $table->text('setting_value')->nullable();
                $table->text('description')->nullable();
                $table->string('category')->default('platform');
                $table->boolean('is_editable')->default(true);
                $table->uuid('last_modified_by')->nullable();
                $table->timestamp('last_modified_at')->nullable();
                $table->timestamp('created_at');
                $table->timestamp('updated_at');

                $table->foreign('last_modified_by')->references('id')->on('users')->nullOnDelete();
                $table->index('setting_key');
            });
        }

        $this->ensureOrdersIndex();
        $this->ensureEventsIndex();
        $this->ensureUsersIndex();
        $this->ensureFraudEventsIndex();
        $this->ensurePayoutsIndex();
        $this->ensurePaymentsIndex();
    }

    private function ensureOrdersIndex(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        $hasCreatedAt = $this->indexExists('orders', 'idx_orders_created_at');

        if (! $hasCreatedAt) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('created_at', 'idx_orders_created_at');
            });
        }
    }

    private function ensureEventsIndex(): void
    {
        if (! Schema::hasTable('events')) {
            return;
        }

        $hasComposite = $this->indexExists('events', 'idx_events_status_created_at');

        if (! $hasComposite) {
            Schema::table('events', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'idx_events_status_created_at');
            });
        }
    }

    private function ensureUsersIndex(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! $this->columnExists('users', 'status')) {
            return;
        }

        $hasComposite = $this->indexExists('users', 'idx_users_role_status_created_at');

        if (! $hasComposite) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['role', 'status', 'created_at'], 'idx_users_role_status_created_at');
            });
        }
    }

    private function ensureFraudEventsIndex(): void
    {
        if (! Schema::hasTable('fraud_events')) {
            return;
        }

        $hasComposite = $this->indexExists('fraud_events', 'idx_fraud_events_status_risk_score_created_at');

        if (! $hasComposite) {
            Schema::table('fraud_events', function (Blueprint $table) {
                $table->index(['status', 'risk_score', 'created_at'], 'idx_fraud_events_status_risk_score_created_at');
            });
        }
    }

    private function ensurePayoutsIndex(): void
    {
        if (! Schema::hasTable('payouts')) {
            return;
        }

        $hasComposite = $this->indexExists('payouts', 'idx_payouts_status_created_at');

        if (! $hasComposite) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'idx_payouts_status_created_at');
            });
        }
    }

    private function ensurePaymentsIndex(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (! $this->columnExists('payments', 'payment_method')) {
            return;
        }

        $hasComposite = $this->indexExists('payments', 'idx_payments_status_created_at_payment_method');

        if (! $hasComposite) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['status', 'created_at', 'payment_method'], 'idx_payments_status_created_at_payment_method');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index]
            );

            return $row !== null;
        }

        if (DB::getDriverName() === 'sqlite') {
            $rows = DB::select('PRAGMA index_list(' . $table . ')');

            foreach ($rows as $idx) {
                if ($idx->name === $index) {
                    return true;
                }
            }

            return false;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return $row !== null;
    }

    private function columnExists(string $table, string $column): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            $rows = DB::select('PRAGMA table_info(' . $table . ')');
            foreach ($rows as $col) {
                if ($col->name === $column) {
                    return true;
                }
            }

            return false;
        }

        $row = DB::selectOne(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?',
            [$table, $column]
        );

        return $row !== null;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};

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
        $indexes = DB::select('PRAGMA index_list(orders)');
        $hasCreatedAt = false;
        foreach ($indexes as $index) {
            if ($index->name === 'idx_orders_created_at') {
                $hasCreatedAt = true;
                break;
            }
        }
        if (! $hasCreatedAt) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->index('created_at', 'idx_orders_created_at');
                });
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }
    }

    private function ensureEventsIndex(): void
    {
        if (! Schema::hasTable('events')) {
            return;
        }
        $indexes = DB::select('PRAGMA index_list(events)');
        $hasComposite = false;
        foreach ($indexes as $index) {
            if ($index->name === 'idx_events_status_created_at') {
                $hasComposite = true;
                break;
            }
        }
        if (! $hasComposite) {
            try {
                Schema::table('events', function (Blueprint $table) {
                    $table->index(['status', 'created_at'], 'idx_events_status_created_at');
                });
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }
    }

    private function ensureUsersIndex(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }
        $indexes = DB::select('PRAGMA index_list(users)');
        $hasComposite = false;
        foreach ($indexes as $index) {
            if ($index->name === 'idx_users_role_status_created_at') {
                $hasComposite = true;
                break;
            }
        }
        if (! $hasComposite) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->index(['role', 'status', 'created_at'], 'idx_users_role_status_created_at');
                });
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }
    }

    private function ensureFraudEventsIndex(): void
    {
        if (! Schema::hasTable('fraud_events')) {
            return;
        }
        $indexes = DB::select('PRAGMA index_list(fraud_events)');
        $hasComposite = false;
        foreach ($indexes as $index) {
            if ($index->name === 'idx_fraud_events_status_risk_score_created_at') {
                $hasComposite = true;
                break;
            }
        }
        if (! $hasComposite) {
            try {
                Schema::table('fraud_events', function (Blueprint $table) {
                    $table->index(['status', 'risk_score', 'created_at'], 'idx_fraud_events_status_risk_score_created_at');
                });
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }
    }

    private function ensurePayoutsIndex(): void
    {
        if (! Schema::hasTable('payouts')) {
            return;
        }
        $indexes = DB::select('PRAGMA index_list(payouts)');
        $hasComposite = false;
        foreach ($indexes as $index) {
            if ($index->name === 'idx_payouts_status_created_at') {
                $hasComposite = true;
                break;
            }
        }
        if (! $hasComposite) {
            try {
                Schema::table('payouts', function (Blueprint $table) {
                    $table->index(['status', 'created_at'], 'idx_payouts_status_created_at');
                });
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }
    }

    private function ensurePaymentsIndex(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }
        $indexes = DB::select('PRAGMA index_list(payments)');
        $hasComposite = false;
        foreach ($indexes as $index) {
            if ($index->name === 'idx_payments_status_created_at_payment_method') {
                $hasComposite = true;
                break;
            }
        }
        if (! $hasComposite) {
            try {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index(['status', 'created_at', 'payment_method'], 'idx_payments_status_created_at_payment_method');
                });
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};

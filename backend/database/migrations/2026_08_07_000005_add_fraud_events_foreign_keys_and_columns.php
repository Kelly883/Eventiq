<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            try {
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            } catch (\Exception $e) {
            }

            try {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            } catch (\Exception $e) {
            }

            try {
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            } catch (\Exception $e) {
            }

            try {
                $table->foreign('escalated_to')->references('id')->on('users')->onDelete('set null');
            } catch (\Exception $e) {
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
};
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
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildSqlite();
        } elseif ($driver === 'pgsql') {
            $this->fixPostgreSql();
        } else {
            $this->fixMySql();
        }
    }

    private function rebuildSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::transaction(function () {
                Schema::dropIfExists('payout_calculations');
                Schema::dropIfExists('payouts');
                Schema::dropIfExists('settlement_policies');

                $this->createSettlementPolicies();
                $this->createPayouts();
                $this->createPayoutCalculations();
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function createSettlementPolicies(): void
    {
        Schema::create('settlement_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('organizer_tier')->default('standard');
            $table->foreignId('organizer_id')->nullable();
            $table->decimal('platform_commission_percentage', 5, 2);
            $table->decimal('processing_fee_percentage', 5, 2);
            $table->string('payout_frequency');
            $table->decimal('minimum_payout_threshold', 10, 2);
            $table->integer('payout_hold_days');
            $table->boolean('requires_approval')->default(false);
            $table->decimal('auto_approve_threshold', 10, 2)->nullable();
            $table->integer('max_retries')->default(3);
            $table->decimal('retry_backoff_multiplier', 3, 2)->default(2.0);
            $table->decimal('tax_withholding_percentage', 5, 2)->nullable();
            $table->json('allowed_payout_methods')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('organizer_id')->references('id')->on('organizers')->onDelete('set null');
            $table->unique('organizer_tier');
        });
    }

    private function createPayouts(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('organizer_id');
            $table->timestamp('settlement_period_start_date');
            $table->timestamp('settlement_period_end_date');
            $table->decimal('gross_revenue', 12, 2);
            $table->decimal('refunds_deducted', 12, 2);
            $table->decimal('net_revenue', 12, 2);
            $table->decimal('platform_commission_percentage', 5, 2);
            $table->decimal('platform_commission_amount', 12, 2);
            $table->decimal('processing_fee_percentage', 5, 2);
            $table->decimal('processing_fee_amount', 12, 2);
            $table->decimal('tax_withholding_percentage', 5, 2)->nullable();
            $table->decimal('tax_withholding_amount', 12, 2)->nullable();
            $table->decimal('payout_amount', 12, 2);
            $table->string('payout_method');
            $table->string('payment_gateway_payout_id')->nullable();
            $table->json('payment_gateway_response')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('calculated_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->foreign('organizer_id')->references('id')->on('organizers')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

            $table->index('organizer_id');
            $table->index('status');
            $table->index('settlement_period_start_date');
            $table->index('settlement_period_end_date');
            $table->index(['organizer_id', 'status']);
        });
    }

    private function createPayoutCalculations(): void
    {
        Schema::create('payout_calculations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payout_id');
            $table->foreignId('organizer_id');
            $table->timestamp('settlement_period_start_date');
            $table->timestamp('settlement_period_end_date');
            $table->json('event_ids')->nullable();
            $table->json('order_ids')->nullable();
            $table->json('refund_request_ids')->nullable();
            $table->integer('total_order_count')->default(0);
            $table->integer('total_tickets_sold')->default(0);
            $table->integer('total_refunds_processed')->default(0);
            $table->json('calculation_details')->nullable();
            $table->timestamp('calculated_at');
            $table->string('calculated_by');
            $table->timestamp('created_at');

            $table->foreign('payout_id')->references('id')->on('payouts')->cascadeOnDelete();
            $table->foreign('organizer_id')->references('id')->on('organizers')->cascadeOnDelete();

            $table->index('payout_id');
            $table->index('organizer_id');
        });
    }

    private function fixMySql(): void
    {
        $columnsToAdd = [
                'organizer_tier' => "varchar(20) DEFAULT 'standard' AFTER name",
                'organizer_id' => 'uuid NULL AFTER organizer_tier',
                'platform_commission_percentage' => 'decimal(5,2) AFTER organizer_id',
                'processing_fee_percentage' => 'decimal(5,2) AFTER platform_commission_percentage',
                'payout_frequency' => "varchar(20) AFTER processing_fee_percentage",
                'minimum_payout_threshold' => 'decimal(10,2) AFTER payout_frequency',
                'payout_hold_days' => 'int AFTER minimum_payout_threshold',
                'requires_approval' => 'boolean DEFAULT false AFTER payout_hold_days',
                'auto_approve_threshold' => 'decimal(10,2) NULL AFTER requires_approval',
                'max_retries' => 'int DEFAULT 3 AFTER auto_approve_threshold',
                'retry_backoff_multiplier' => 'decimal(3,2) DEFAULT 2.0 AFTER max_retries',
                'tax_withholding_percentage' => 'decimal(5,2) NULL AFTER retry_backoff_multiplier',
                'allowed_payout_methods' => 'json NULL AFTER tax_withholding_percentage',
            ];
        $missingSettlementPoliciesColumns = [];
        foreach ($columnsToAdd as $column => $definition) {
            if (! Schema::hasColumn('settlement_policies', $column)) {
                $missingSettlementPoliciesColumns[$column] = $definition;
            }
        }
        Schema::table('settlement_policies', function (Blueprint $table) use ($missingSettlementPoliciesColumns) {
            foreach ($missingSettlementPoliciesColumns as $column => $definition) {
                        try {
                            $columnDef = DB::getDriverName() === 'mysql' ? $definition : preg_replace('/\s+AFTER\s+\w+/', '', $definition);
                            DB::statement("ALTER TABLE settlement_policies ADD COLUMN {$column} {$columnDef}");
                        } catch (\Throwable $e) {
                            // Column may already exist
                        }
                    }
            try {
                DB::statement('ALTER TABLE settlement_policies ADD UNIQUE INDEX organizer_tier (organizer_tier)');
            } catch (\Throwable $e) {
                // Index may already exist
            }
        });

        $columnsToAdd = [
                'organizer_id' => 'uuid AFTER id',
                'settlement_period_start_date' => 'timestamp AFTER organizer_id',
                'settlement_period_end_date' => 'timestamp AFTER settlement_period_start_date',
                'gross_revenue' => 'decimal(12,2) AFTER settlement_period_end_date',
                'refunds_deducted' => 'decimal(12,2) AFTER gross_revenue',
                'net_revenue' => 'decimal(12,2) AFTER refunds_deducted',
                'platform_commission_percentage' => 'decimal(5,2) AFTER net_revenue',
                'platform_commission_amount' => 'decimal(12,2) AFTER platform_commission_percentage',
                'processing_fee_percentage' => 'decimal(5,2) AFTER platform_commission_amount',
                'processing_fee_amount' => 'decimal(12,2) AFTER processing_fee_percentage',
                'tax_withholding_percentage' => 'decimal(5,2) NULL AFTER processing_fee_amount',
                'tax_withholding_amount' => 'decimal(12,2) NULL AFTER tax_withholding_percentage',
                'payout_amount' => 'decimal(12,2) AFTER tax_withholding_amount',
                'payout_method' => 'varchar(255) AFTER payout_amount',
                'payment_gateway_payout_id' => 'varchar(255) NULL AFTER payout_method',
                'payment_gateway_response' => 'json NULL AFTER payment_gateway_payout_id',
                'calculated_at' => 'timestamp NULL AFTER payment_gateway_response',
                'approved_by' => 'uuid NULL AFTER calculated_at',
                'approved_at' => 'timestamp NULL AFTER approved_by',
                'processing_started_at' => 'timestamp NULL AFTER approved_at',
                'completed_at' => 'timestamp NULL AFTER processing_started_at',
                'failure_reason' => 'text NULL AFTER completed_at',
                'retry_count' => 'int DEFAULT 0 AFTER failure_reason',
                'next_retry_at' => 'timestamp NULL AFTER retry_count',
            ];
        $missingPayoutsColumns = [];
        foreach ($columnsToAdd as $column => $definition) {
            if (! Schema::hasColumn('payouts', $column)) {
                $missingPayoutsColumns[$column] = $definition;
            }
        }
        Schema::table('payouts', function (Blueprint $table) use ($missingPayoutsColumns) {
            foreach ($missingPayoutsColumns as $column => $definition) {
                        try {
                            $columnDef = DB::getDriverName() === 'mysql' ? $definition : preg_replace('/\s+AFTER\s+\w+/', '', $definition);
                            DB::statement("ALTER TABLE payouts ADD COLUMN {$column} {$columnDef}");
                        } catch (\Throwable $e) {
                            // Column may already exist
                        }
                    }
            try {
                $table->index('organizer_id');
            } catch (\Throwable $e) {
            }
            try {
                $table->index('status');
            } catch (\Throwable $e) {
            }
            try {
                $table->index('settlement_period_start_date');
            } catch (\Throwable $e) {
            }
            try {
                $table->index('settlement_period_end_date');
            } catch (\Throwable $e) {
            }
            try {
                $table->index(['organizer_id', 'status']);
            } catch (\Throwable $e) {
            }
        });

        $columnsToAdd = [
                'organizer_id' => 'uuid AFTER payout_id',
                'settlement_period_start_date' => 'timestamp AFTER organizer_id',
                'settlement_period_end_date' => 'timestamp AFTER settlement_period_start_date',
                'event_ids' => 'json NULL AFTER settlement_period_end_date',
                'order_ids' => 'json NULL AFTER event_ids',
                'refund_request_ids' => 'json NULL AFTER order_ids',
                'total_order_count' => 'int DEFAULT 0 AFTER refund_request_ids',
                'total_tickets_sold' => 'int DEFAULT 0 AFTER total_order_count',
                'total_refunds_processed' => 'int DEFAULT 0 AFTER total_tickets_sold',
                'calculation_details' => 'json NULL AFTER total_refunds_processed',
                'calculated_at' => 'timestamp AFTER calculation_details',
                'calculated_by' => 'varchar(255) AFTER calculated_at',
                'created_at' => 'timestamp AFTER calculated_by',
            ];
        $missingPayoutCalculationsColumns = [];
        foreach ($columnsToAdd as $column => $definition) {
            if (! Schema::hasColumn('payout_calculations', $column)) {
                $missingPayoutCalculationsColumns[$column] = $definition;
            }
        }
        Schema::table('payout_calculations', function (Blueprint $table) use ($missingPayoutCalculationsColumns) {
            foreach ($missingPayoutCalculationsColumns as $column => $definition) {
                        try {
                            $columnDef = DB::getDriverName() === 'mysql' ? $definition : preg_replace('/\s+AFTER\s+\w+/', '', $definition);
                            DB::statement("ALTER TABLE payout_calculations ADD COLUMN {$column} {$columnDef}");
                        } catch (\Throwable $e) {
                            // Column may already exist
                        }
                    }
            try {
                $table->index('payout_id');
            } catch (\Throwable $e) {
            }
            try {
                $table->index('organizer_id');
            } catch (\Throwable $e) {
            }
        });
    }

    private function fixPostgreSql(): void
    {
        $columnsToAdd = [
                'organizer_tier' => "varchar(20) DEFAULT 'standard'",
                'organizer_id' => 'uuid NULL',
                'platform_commission_percentage' => 'decimal(5,2)',
                'processing_fee_percentage' => 'decimal(5,2)',
                'payout_frequency' => "varchar(20)",
                'minimum_payout_threshold' => 'decimal(10,2)',
                'payout_hold_days' => 'int',
                'requires_approval' => 'boolean DEFAULT false',
                'auto_approve_threshold' => 'decimal(10,2) NULL',
                'max_retries' => 'int DEFAULT 3',
                'retry_backoff_multiplier' => 'decimal(3,2) DEFAULT 2.0',
                'tax_withholding_percentage' => 'decimal(5,2) NULL',
                'allowed_payout_methods' => 'json NULL',
            ];
        $missingSettlementPoliciesColumns = [];
        foreach ($columnsToAdd as $column => $definition) {
            if (! Schema::hasColumn('settlement_policies', $column)) {
                $missingSettlementPoliciesColumns[$column] = $definition;
            }
        }
        if (! empty($missingSettlementPoliciesColumns)) {
            foreach ($missingSettlementPoliciesColumns as $column => $definition) {
                DB::statement("ALTER TABLE settlement_policies ADD COLUMN {$column} {$definition}");
            }
        }

        if (! $this->indexExists('settlement_policies', 'settlement_policies_organizer_tier_unique')) {
            Schema::table('settlement_policies', function (Blueprint $table) {
                $table->unique('organizer_tier', 'settlement_policies_organizer_tier_unique');
            });
        }

        $columnsToAdd = [
                'organizer_id' => 'uuid',
                'settlement_period_start_date' => 'timestamp',
                'settlement_period_end_date' => 'timestamp',
                'gross_revenue' => 'decimal(12,2)',
                'refunds_deducted' => 'decimal(12,2)',
                'net_revenue' => 'decimal(12,2)',
                'platform_commission_percentage' => 'decimal(5,2)',
                'platform_commission_amount' => 'decimal(12,2)',
                'processing_fee_percentage' => 'decimal(5,2)',
                'processing_fee_amount' => 'decimal(12,2)',
                'tax_withholding_percentage' => 'decimal(5,2) NULL',
                'tax_withholding_amount' => 'decimal(5,2) NULL',
                'payout_amount' => 'decimal(12,2)',
                'payout_method' => 'varchar(255)',
                'payment_gateway_payout_id' => 'varchar(255) NULL',
                'payment_gateway_response' => 'json NULL',
                'calculated_at' => 'timestamp NULL',
                'approved_by' => 'uuid NULL',
                'approved_at' => 'timestamp NULL',
                'processing_started_at' => 'timestamp NULL',
                'completed_at' => 'timestamp NULL',
                'failure_reason' => 'text NULL',
                'retry_count' => 'int DEFAULT 0',
                'next_retry_at' => 'timestamp NULL',
            ];
        $missingPayoutsColumns = [];
        foreach ($columnsToAdd as $column => $definition) {
            if (! Schema::hasColumn('payouts', $column)) {
                $missingPayoutsColumns[$column] = $definition;
            }
        }
        if (! empty($missingPayoutsColumns)) {
            foreach ($missingPayoutsColumns as $column => $definition) {
                DB::statement("ALTER TABLE payouts ADD COLUMN {$column} {$definition}");
            }
        }

        if (! $this->indexExists('payouts', 'payouts_organizer_id_index')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->index('organizer_id', 'payouts_organizer_id_index');
            });
        }

        if (! $this->indexExists('payouts', 'payouts_status_index')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->index('status', 'payouts_status_index');
            });
        }

        if (! $this->indexExists('payouts', 'payouts_settlement_period_start_date_index')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->index('settlement_period_start_date', 'payouts_settlement_period_start_date_index');
            });
        }

        if (! $this->indexExists('payouts', 'payouts_settlement_period_end_date_index')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->index('settlement_period_end_date', 'payouts_settlement_period_end_date_index');
            });
        }

        if (! $this->indexExists('payouts', 'payouts_organizer_id_status_index')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->index(['organizer_id', 'status'], 'payouts_organizer_id_status_index');
            });
        }

        $columnsToAdd = [
                'organizer_id' => 'uuid',
                'settlement_period_start_date' => 'timestamp',
                'settlement_period_end_date' => 'timestamp',
                'event_ids' => 'json NULL',
                'order_ids' => 'json NULL',
                'refund_request_ids' => 'json NULL',
                'total_order_count' => 'int DEFAULT 0',
                'total_tickets_sold' => 'int DEFAULT 0',
                'total_refunds_processed' => 'int DEFAULT 0',
                'calculation_details' => 'json NULL',
                'calculated_at' => 'timestamp',
                'calculated_by' => 'varchar(255)',
                'created_at' => 'timestamp',
            ];
        $missingPayoutCalculationsColumns = [];
        foreach ($columnsToAdd as $column => $definition) {
            if (! Schema::hasColumn('payout_calculations', $column)) {
                $missingPayoutCalculationsColumns[$column] = $definition;
            }
        }
        if (! empty($missingPayoutCalculationsColumns)) {
            foreach ($missingPayoutCalculationsColumns as $column => $definition) {
                DB::statement("ALTER TABLE payout_calculations ADD COLUMN {$column} {$definition}");
            }
        }

        if (! $this->indexExists('payout_calculations', 'payout_calculations_payout_id_index')) {
            Schema::table('payout_calculations', function (Blueprint $table) {
                $table->index('payout_id', 'payout_calculations_payout_id_index');
            });
        }

        if (! $this->indexExists('payout_calculations', 'payout_calculations_organizer_id_index')) {
            Schema::table('payout_calculations', function (Blueprint $table) {
                $table->index('organizer_id', 'payout_calculations_organizer_id_index');
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_calculations');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('settlement_policies');
    }
};

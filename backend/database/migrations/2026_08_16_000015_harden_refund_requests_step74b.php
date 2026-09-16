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
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqlite();
        } elseif (DB::getDriverName() === 'mysql') {
            $this->fixMySql();
        } else {
            $this->fixPostgreSql();
        }
    }

    private function rebuildSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::transaction(function () {
                Schema::dropIfExists('refund_appeals');
                Schema::dropIfExists('refund_requests');

                $this->createRefundRequests();
                $this->createRefundAppeals();
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function createRefundRequests(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('ticket_id');
            $table->uuid('order_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->foreignId('event_id')->nullable();
            $table->decimal('original_amount', 10, 2);
            $table->decimal('refund_amount', 10, 2);
            $table->decimal('refund_percentage', 5, 2);
            $table->string('reason', 50);
            $table->text('explanation')->nullable();
            $table->string('refund_method');
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('payment_gateway_refund_id')->nullable();
            $table->json('payment_gateway_response')->nullable();
            $table->integer('appeal_count')->default(0);
            $table->timestamp('last_appeal_at')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['user_id', 'status']);
            $table->index(['event_id', 'status']);
            $table->index('ticket_id');
            $table->index(['status', 'created_at']);
            $table->unique('ticket_id');
        });
    }

    private function createRefundAppeals(): void
    {
        Schema::create('refund_appeals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('refund_request_id');
            $table->uuid('user_id');
            $table->text('appeal_reason');
            $table->string('status')->default('pending');
            $table->uuid('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('refund_request_id')->references('id')->on('refund_requests')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();

            $table->index('refund_request_id');
            $table->index('user_id');
        });
    }

    private function fixMySql(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            // Change user_id FK from CASCADE to SET NULL
            try {
                DB::statement('ALTER TABLE refund_requests DROP FOREIGN KEY refund_requests_user_id_foreign');
            } catch (\Throwable $e) {
                // FK may not exist
            }
            try {
                DB::statement('ALTER TABLE refund_requests ADD CONSTRAINT refund_requests_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
                // FK may already exist
            }

            // Change event_id FK from CASCADE to SET NULL
            try {
                DB::statement('ALTER TABLE refund_requests DROP FOREIGN KEY refund_requests_event_id_foreign');
            } catch (\Throwable $e) {
                // FK may not exist
            }
            try {
                DB::statement('ALTER TABLE refund_requests ADD CONSTRAINT refund_requests_event_id_foreign FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
                // FK may already exist
            }

            // Change reason to VARCHAR(50)
            try {
                DB::statement('ALTER TABLE refund_requests MODIFY COLUMN reason VARCHAR(50) NOT NULL');
            } catch (\Throwable $e) {
                // Column may already be correct type
            }

            // Add status check constraint (MySQL only, ignored by SQLite)
            try {
                DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT chk_refund_requests_status CHECK (status IN ('pending', 'approved', 'rejected', 'processing', 'completed', 'failed'))");
            } catch (\Throwable $e) {
                // Constraint may already exist
            }

            // Add reason check constraint
            try {
                DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT chk_refund_requests_reason CHECK (reason IN ('event_cancelled', 'personal_circumstances', 'duplicate_purchase', 'other'))");
            } catch (\Throwable $e) {
                // Constraint may already exist
            }

            // Add refund_method check constraint
            try {
                DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT chk_refund_requests_refund_method CHECK (refund_method IN ('original_payment_method', 'store_credit', 'alternative_payment_method'))");
            } catch (\Throwable $e) {
                // Constraint may already exist
            }

            // Add composite index for admin dashboard
            try {
                $table->index(['status', 'created_at'], 'idx_refund_requests_status_created_at');
            } catch (\Throwable $e) {
                // Index may already exist
            }

            // Add unique index on ticket_id to enforce one refund request per ticket
            try {
                $table->unique('ticket_id', 'idx_refund_requests_ticket_id_unique');
            } catch (\Throwable $e) {
                // Index may already exist
            }
        });
    }

    private function fixPostgreSql(): void
    {
        $self = $this;

        Schema::table('refund_requests', function (Blueprint $table) use ($self) {
            // Change user_id FK from CASCADE to SET NULL
            if ($self->foreignKeyExists('refund_requests', 'user_id')) {
                $table->dropForeign(['user_id']);
            }
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Change event_id FK from CASCADE to SET NULL
            if ($self->foreignKeyExists('refund_requests', 'event_id')) {
                $table->dropForeign(['event_id']);
            }
            $table->foreign('event_id')->references('id')->on('events')->nullOnDelete();

            // Add composite index for admin dashboard
            if (! $self->indexExists('refund_requests', 'idx_refund_requests_status_created_at')) {
                $table->index(['status', 'created_at'], 'idx_refund_requests_status_created_at');
            }

            // Add unique index on ticket_id to enforce one refund request per ticket
            if (! $self->indexExists('refund_requests', 'idx_refund_requests_ticket_id_unique')) {
                $table->unique('ticket_id', 'idx_refund_requests_ticket_id_unique');
            }
        });

        // Change reason to VARCHAR(50) NOT NULL (PostgreSQL-compatible)
        try {
            DB::statement('ALTER TABLE refund_requests ALTER COLUMN reason TYPE VARCHAR(50) USING reason::varchar(50)');
            DB::statement('ALTER TABLE refund_requests ALTER COLUMN reason SET NOT NULL');
        } catch (\Throwable $e) {
            // Column may already be correct type
        }

        // Add status check constraint
        try {
            DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT chk_refund_requests_status CHECK (status IN ('pending', 'approved', 'rejected', 'processing', 'completed', 'failed'))");
        } catch (\Throwable $e) {
            // Constraint may already exist
        }

        // Add reason check constraint
        try {
            DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT chk_refund_requests_reason CHECK (reason IN ('event_cancelled', 'personal_circumstances', 'duplicate_purchase', 'other'))");
        } catch (\Throwable $e) {
            // Constraint may already exist
        }

        // Add refund_method check constraint
        try {
            DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT chk_refund_requests_refund_method CHECK (refund_method IN ('original_payment_method', 'store_credit', 'alternative_payment_method'))");
        } catch (\Throwable $e) {
            // Constraint may already exist
        }
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT 1 FROM pg_constraint c
                 JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY(c.conkey)
                 WHERE c.contype = \'f\'
                   AND c.conrelid = ?::regclass
                   AND a.attname = ?
                   AND array_length(c.conkey, 1) = 1',
                [$table, $column]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT constraint_name FROM information_schema.table_constraints 
             WHERE table_schema = current_schema() 
               AND table_name = ? 
               AND constraint_type = \'FOREIGN KEY\'
               AND constraint_name = ?',
            [$table, "{$table}_{$column}_foreign"]
        );

        return $row !== null;
    }

    private function indexExists(string $table, string $indexName): bool
    {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_appeals');
        Schema::dropIfExists('refund_requests');
    }
};

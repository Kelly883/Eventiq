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
                Schema::dropIfExists('audit_logs');
                Schema::create('audit_logs', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->uuid('user_id')->nullable();
                    $table->string('action');
                    $table->string('target_type');
                    $table->uuid('target_id')->nullable();
                    $table->string('status')->default('success');
                    $table->string('ip_address')->nullable();
                    $table->text('user_agent')->nullable();
                    $table->json('geolocation')->nullable();
                    $table->json('request_data')->nullable();
                    $table->json('response_data')->nullable();
                    $table->json('changed_fields')->nullable();
                    $table->text('error_message')->nullable();
                    $table->string('error_code')->nullable();
                    $table->string('compliance_classification')->default('internal');
                    $table->timestamp('retention_date')->nullable();
                    $table->json('metadata')->nullable();
                    $table->timestamp('created_at');
                    $table->timestamp('updated_at');
                    $table->softDeletes();

                    $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

                    $table->index('user_id');
                    $table->index('target_id');
                    $table->index('ip_address');
                    $table->index('created_at');
                    $table->index('retention_date');
                    $table->index(['user_id', 'created_at']);
                    $table->index(['action', 'status']);
                    $table->index('compliance_classification');
                });
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function fixMySql(): void
    {
        $hasAuditLogsDeletedAt = Schema::hasColumn('audit_logs', 'deleted_at');
        $columnsToAdd = [
                'user_id' => 'uuid NULL AFTER id',
                'target_type' => 'varchar(255) AFTER action',
                'target_id' => 'uuid NULL AFTER target_type',
                'status' => "varchar(20) DEFAULT 'success' AFTER target_id",
                'ip_address' => 'varchar(45) NULL AFTER status',
                'user_agent' => 'text NULL AFTER ip_address',
                'geolocation' => 'json NULL AFTER user_agent',
                'request_data' => 'json NULL AFTER geolocation',
                'response_data' => 'json NULL AFTER request_data',
                'changed_fields' => 'json NULL AFTER response_data',
                'error_message' => 'text NULL AFTER changed_fields',
                'error_code' => 'varchar(100) NULL AFTER error_message',
                'compliance_classification' => "varchar(50) DEFAULT 'internal' AFTER error_code",
                'retention_date' => 'timestamp NULL AFTER compliance_classification',
                'metadata' => 'json NULL AFTER retention_date',
            ];
        $missingAuditLogsColumns = [];
        foreach ($columnsToAdd as $column => $definition) {
            if (! Schema::hasColumn('audit_logs', $column)) {
                $missingAuditLogsColumns[$column] = $definition;
            }
        }
        Schema::table('audit_logs', function (Blueprint $table) use ($hasAuditLogsDeletedAt, $missingAuditLogsColumns) {
            foreach ($missingAuditLogsColumns as $column => $definition) {
                try {
                    $columnDef = DB::getDriverName() === 'mysql' ? $definition : preg_replace('/\s+AFTER\s+\w+/', '', $definition);
                    DB::statement("ALTER TABLE audit_logs ADD COLUMN {$column} {$columnDef}");
                } catch (\Throwable $e) {
                    // Column may already exist
                }
            }
            if (! $hasAuditLogsDeletedAt) {
                $table->softDeletes();
            }
            $indexesToAdd = [
                'idx_audit_logs_user_id' => 'user_id',
                'idx_audit_logs_target_id' => 'target_id',
                'idx_audit_logs_ip_address' => 'ip_address',
                'idx_audit_logs_created_at' => 'created_at',
                'idx_audit_logs_retention_date' => 'retention_date',
                'idx_audit_logs_user_created' => ['user_id', 'created_at'],
                'idx_audit_logs_action_status' => ['action', 'status'],
                'idx_audit_logs_compliance' => 'compliance_classification',
            ];
            foreach ($indexesToAdd as $name => $columns) {
                if (! $this->indexExists('audit_logs', $name)) {
                    try {
                        if (is_array($columns)) {
                            $table->index($columns, $name);
                        } else {
                            $table->index($columns, $name);
                        }
                    } catch (\Throwable $e) {
                        // Index may already exist
                    }
                }
            }
        });
    }

    private function fixPostgreSql(): void
    {
        $hasAuditLogsDeletedAt = Schema::hasColumn('audit_logs', 'deleted_at');

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'user_id')) {
                $table->uuid('user_id')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'target_type')) {
                $table->string('target_type', 255);
            }
            if (! Schema::hasColumn('audit_logs', 'target_id')) {
                $table->uuid('target_id')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'status')) {
                $table->string('status', 20)->default('success');
            }
            if (! Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->text('user_agent')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'geolocation')) {
                $table->json('geolocation')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'request_data')) {
                $table->json('request_data')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'response_data')) {
                $table->json('response_data')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'changed_fields')) {
                $table->json('changed_fields')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'error_message')) {
                $table->text('error_message')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'error_code')) {
                $table->string('error_code', 100)->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'compliance_classification')) {
                $table->string('compliance_classification', 50)->default('internal');
            }
            if (! Schema::hasColumn('audit_logs', 'retention_date')) {
                $table->timestamp('retention_date')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });

        if (! $hasAuditLogsDeletedAt) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        $indexesToAdd = [
            'idx_audit_logs_user_id' => 'user_id',
            'idx_audit_logs_target_id' => 'target_id',
            'idx_audit_logs_ip_address' => 'ip_address',
            'idx_audit_logs_created_at' => 'created_at',
            'idx_audit_logs_retention_date' => 'retention_date',
            'idx_audit_logs_user_created' => ['user_id', 'created_at'],
            'idx_audit_logs_action_status' => ['action', 'status'],
            'idx_audit_logs_compliance' => 'compliance_classification',
        ];
        foreach ($indexesToAdd as $name => $columns) {
            if (! $this->indexExists('audit_logs', $name)) {
                Schema::table('audit_logs', function (Blueprint $table) use ($columns, $name) {
                    if (is_array($columns)) {
                        $table->index($columns, $name);
                    } else {
                        $table->index($columns, $name);
                    }
                });
            }
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
        Schema::dropIfExists('audit_logs');
    }
};

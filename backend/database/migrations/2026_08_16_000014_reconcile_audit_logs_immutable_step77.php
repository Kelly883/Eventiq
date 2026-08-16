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
        Schema::table('audit_logs', function (Blueprint $table) {
            try {
                $table->uuid('id')->primary()->change();
            } catch (\Throwable $e) {
                // May already be UUID
            }
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
            foreach ($columnsToAdd as $column => $definition) {
                if (! Schema::hasColumn('audit_logs', $column)) {
                    try {
                        DB::statement("ALTER TABLE audit_logs ADD COLUMN {$column} {$definition}");
                    } catch (\Throwable $e) {
                        // Column may already exist
                    }
                }
            }
            if (! Schema::hasColumn('audit_logs', 'deleted_at')) {
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
                $indexes = DB::select('PRAGMA index_list(audit_logs)');
                $exists = false;
                foreach ($indexes as $index) {
                    if ($index->name === $name) {
                        $exists = true;
                        break;
                    }
                }
                if (! $exists) {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

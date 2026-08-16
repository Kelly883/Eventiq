<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rebuilds push_notification_devices and push_notification_templates
     * to match the Step 73 spec. The original migrations used legacy
     * column names (fcm_token, platform) and attempted SQLite-incompatible
     * ALTER TABLE ... CHANGE operations.
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
                if (Schema::hasTable('push_notification_devices')) {
                    Schema::dropIfExists('push_notification_devices');
                }
                if (Schema::hasTable('push_notification_templates')) {
                    Schema::dropIfExists('push_notification_templates');
                }

                $this->createDevicesTable();
                $this->createTemplatesTable();
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function createDevicesTable(): void
    {
        Schema::create('push_notification_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('token')->unique();
            $table->string('provider');
            $table->enum('device_type', ['web', 'ios', 'android']);
            $table->timestamps();

            $table->index('user_id');
            $table->index('token');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    private function createTemplatesTable(): void
    {
        Schema::create('push_notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->string('title', 65);
            $table->string('body', 178);
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active'], 'idx_push_templates_type_active');
            $table->index('is_active', 'idx_push_templates_is_active');
        });
    }

    private function fixMySql(): void
    {
        Schema::table('push_notification_devices', function (Blueprint $table) {
            try {
                $table->uuid('id')->primary()->change();
            } catch (\Throwable $e) {
                // May already be UUID
            }

            if (Schema::hasColumn('push_notification_devices', 'fcm_token')) {
                try {
                    $table->renameColumn('fcm_token', 'token');
                } catch (\Throwable $e) {
                    // Column may not exist or already renamed
                }
            }

            if (! Schema::hasColumn('push_notification_devices', 'provider')) {
                $table->string('provider')->after('user_id');
            }

            if (! Schema::hasColumn('push_notification_devices', 'device_type')) {
                $table->enum('device_type', ['web', 'ios', 'android'])->after('provider');
            }

            if (! Schema::hasColumn('push_notification_devices', 'platform')) {
                // No-op, legacy column may still exist
            }

            try {
                $table->index('user_id');
            } catch (\Throwable $e) {
                // Index may already exist
            }

            try {
                $table->index('token');
            } catch (\Throwable $e) {
                // Index may already exist
            }
        });

        Schema::table('push_notification_templates', function (Blueprint $table) {
            if (Schema::hasColumn('push_notification_templates', 'body')) {
                try {
                    $table->string('body', 178)->change();
                } catch (\Throwable $e) {
                    // May already be correct type
                }
            }

            try {
                $table->index(['type', 'is_active'], 'idx_push_templates_type_active');
            } catch (\Throwable $e) {
                // Index may already exist
            }

            try {
                $table->index('is_active', 'idx_push_templates_is_active');
            } catch (\Throwable $e) {
                // Index may already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_templates');
        Schema::dropIfExists('push_notification_devices');
    }
};

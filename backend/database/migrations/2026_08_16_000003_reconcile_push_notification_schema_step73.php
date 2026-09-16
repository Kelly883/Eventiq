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
        $hasPushNotificationDevicesFcmToken = Schema::hasColumn('push_notification_devices', 'fcm_token');
        $hasPushNotificationDevicesProvider = Schema::hasColumn('push_notification_devices', 'provider');
        $hasPushNotificationDevicesDeviceType = Schema::hasColumn('push_notification_devices', 'device_type');
        $hasPushNotificationDevicesToken = Schema::hasColumn('push_notification_devices', 'token');
        $hasPushNotificationDevicesPlatform = Schema::hasColumn('push_notification_devices', 'platform');
        Schema::table('push_notification_devices', function (Blueprint $table) use ($hasPushNotificationDevicesFcmToken, $hasPushNotificationDevicesProvider, $hasPushNotificationDevicesDeviceType, $hasPushNotificationDevicesToken, $hasPushNotificationDevicesPlatform) {
            // STATE A: fcm_token exists, token does not exist → rename fcm_token to token
            if ($hasPushNotificationDevicesFcmToken && ! $hasPushNotificationDevicesToken) {
                $table->renameColumn('fcm_token', 'token');
            }

            // STATE B: token exists, fcm_token does not exist → no-op for rename
            // STATE C: neither exists → no-op (both conditions above are false)

            // STATE D: BOTH fcm_token AND token exist → reconcile safely
            if ($hasPushNotificationDevicesFcmToken && $hasPushNotificationDevicesToken) {
                // Check for rows where both columns have different non-empty values → abort
                $conflict = DB::selectOne(
                    'SELECT COUNT(*) as cnt FROM push_notification_devices 
                     WHERE fcm_token IS NOT NULL AND fcm_token != ""  
                       AND token IS NOT NULL AND token != ""  
                       AND fcm_token != token'
                );

                if ($conflict && $conflict->cnt > 0) {
                    throw new \RuntimeException(
                        'Migration aborted: push_notification_devices contains rows where fcm_token and token have different non-empty values. '
                        . 'Cannot safely reconcile. Please resolve manually: update conflicting rows so that fcm_token and token match, then re-run migration.'
                    );
                }

                // Check for rows where token is NULL/empty and fcm_token has non-empty value
                $needCopy = DB::selectOne(
                    'SELECT COUNT(*) as cnt FROM push_notification_devices 
                     WHERE (fcm_token IS NOT NULL AND fcm_token != "") 
                       AND (token IS NULL OR token = "")'
                );

                // Copy fcm_token into token for rows where token is empty/missing
                if ($needCopy && $needCopy->cnt > 0) {
                    DB::table('push_notification_devices')
                        ->where(function ($query) {
                            $query->whereNotNull('fcm_token')
                                ->where('fcm_token', '!=', '');
                        })
                        ->where(function ($query) {
                            $query->whereNull('token')
                                ->orWhere('token', '');
                        })
                        ->update(['token' => DB::raw('fcm_token')]);
                }

                // Only drop fcm_token after data reconciliation is proven safe.
                $table->dropColumn('fcm_token');
            }

            if (! $hasPushNotificationDevicesProvider) {
                $table->string('provider')->after('user_id');
            }

            if (! $hasPushNotificationDevicesDeviceType) {
                $table->enum('device_type', ['web', 'ios', 'android'])->after('provider');
            }

            if (! $hasPushNotificationDevicesPlatform) {
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

        $hasPushNotificationTemplatesBody = Schema::hasColumn('push_notification_templates', 'body');
        Schema::table('push_notification_templates', function (Blueprint $table) use ($hasPushNotificationTemplatesBody) {
            if ($hasPushNotificationTemplatesBody) {
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

    private function fixPostgreSql(): void
    {
        $hasPushNotificationDevicesFcmToken = Schema::hasColumn('push_notification_devices', 'fcm_token');
        $hasPushNotificationDevicesProvider = Schema::hasColumn('push_notification_devices', 'provider');
        $hasPushNotificationDevicesDeviceType = Schema::hasColumn('push_notification_devices', 'device_type');
        $hasPushNotificationDevicesToken = Schema::hasColumn('push_notification_devices', 'token');
        $hasPushNotificationDevicesPlatform = Schema::hasColumn('push_notification_devices', 'platform');
        Schema::table('push_notification_devices', function (Blueprint $table) use ($hasPushNotificationDevicesFcmToken, $hasPushNotificationDevicesProvider, $hasPushNotificationDevicesDeviceType, $hasPushNotificationDevicesToken, $hasPushNotificationDevicesPlatform) {
            // STATE A: fcm_token exists, token does not exist → rename fcm_token to token
            if ($hasPushNotificationDevicesFcmToken && ! $hasPushNotificationDevicesToken) {
                $table->renameColumn('fcm_token', 'token');
            }

            // STATE B: token exists, fcm_token does not exist → no-op for rename
            // STATE C: neither exists → no-op (both conditions above are false)

            // STATE D: BOTH fcm_token AND token exist → reconcile safely
            if ($hasPushNotificationDevicesFcmToken && $hasPushNotificationDevicesToken) {
                // Check for rows where both columns have different non-empty values → abort
                $conflict = DB::selectOne(
                    'SELECT COUNT(*) as cnt FROM push_notification_devices 
                     WHERE fcm_token IS NOT NULL AND fcm_token != \'\'  
                       AND token IS NOT NULL AND token != \'\'  
                       AND fcm_token != token'
                );

                if ($conflict && $conflict->cnt > 0) {
                    throw new \RuntimeException(
                        'Migration aborted: push_notification_devices contains rows where fcm_token and token have different non-empty values. '
                        . 'Cannot safely reconcile. Please resolve manually: update conflicting rows so that fcm_token and token match, then re-run migration.'
                    );
                }

                // Check for rows where token is NULL/empty and fcm_token has non-empty value
                $needCopy = DB::selectOne(
                    'SELECT COUNT(*) as cnt FROM push_notification_devices 
                     WHERE (fcm_token IS NOT NULL AND fcm_token != \'\') 
                       AND (token IS NULL OR token = \'\')'
                );

                // Copy fcm_token into token for rows where token is NULL/empty
                if ($needCopy && $needCopy->cnt > 0) {
                    DB::table('push_notification_devices')
                        ->where(function ($query) {
                            $query->whereNotNull('fcm_token')
                                ->where('fcm_token', '!=', '');
                        })
                        ->where(function ($query) {
                            $query->whereNull('token')
                                ->orWhere('token', '');
                        })
                        ->update(['token' => DB::raw('fcm_token')]);
                }

                // Only drop fcm_token after data reconciliation is proven safe.
                $table->dropColumn('fcm_token');
            }

            if (! $hasPushNotificationDevicesProvider) {
                $table->string('provider');
            }

            if (! $hasPushNotificationDevicesDeviceType) {
                $table->enum('device_type', ['web', 'ios', 'android']);
            }

            if (! $hasPushNotificationDevicesPlatform) {
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

        $hasPushNotificationTemplatesBody = Schema::hasColumn('push_notification_templates', 'body');
        Schema::table('push_notification_templates', function (Blueprint $table) use ($hasPushNotificationTemplatesBody) {
            if ($hasPushNotificationTemplatesBody) {
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
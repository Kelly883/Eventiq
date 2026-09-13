<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds missing columns to delivery_preferences: quiet hours, rate
     * limiting, locale/timezone, and soft deletes. The table already
     * has user_id (unique FK), channel toggles, and push fields from
     * previous migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('delivery_preferences')) {
            Schema::create('delivery_preferences', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->unique();
                $table->boolean('email_enabled')->default(true);
                $table->string('email_address')->nullable();
                $table->boolean('email_verified')->default(false);
                $table->boolean('sms_enabled')->default(false);
                $table->string('phone_number')->nullable();
                $table->boolean('phone_verified')->default(false);
                $table->boolean('dashboard_enabled')->default(true);
                $table->string('preferred_channel')->default('email');
                $table->time('quiet_hours_start')->nullable();
                $table->time('quiet_hours_end')->nullable();
                $table->unsignedSmallInteger('max_daily_notifications')->default(10);
                $table->string('language', 10)->default('en');
                $table->string('timezone', 64)->default('UTC');
                $table->timestamps();
                $table->softDeletes();

                $table->index('user_id');
            });

            try {
                Schema::table('delivery_preferences', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                });
            } catch (\Throwable $e) {
            }

            return;
        }

        // ── Quiet Hours ───────────────────────────────────────────
        if (! Schema::hasColumn('delivery_preferences', 'quiet_hours_start')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->time('quiet_hours_start')->nullable()->after('phone_number');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'quiet_hours_end')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->time('quiet_hours_end')->nullable()->after('quiet_hours_start');
            });
        }

        // ── Rate Limiting ─────────────────────────────────────────
        if (! Schema::hasColumn('delivery_preferences', 'max_daily_notifications')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->unsignedSmallInteger('max_daily_notifications')->default(10)->after('quiet_hours_end');
            });
        }

        // ── Locale ────────────────────────────────────────────────
        if (! Schema::hasColumn('delivery_preferences', 'language')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->string('language', 10)->default('en')->after('max_daily_notifications');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'timezone')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->string('timezone', 64)->default('UTC')->after('language');
            });
        }

        // ── Soft Deletes ──────────────────────────────────────────
        if (! Schema::hasColumn('delivery_preferences', 'deleted_at')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasDeliveryPreferencesQuietHoursStart = Schema::hasColumn('delivery_preferences', 'quiet_hours_start');
        $hasDeliveryPreferencesQuietHoursEnd = Schema::hasColumn('delivery_preferences', 'quiet_hours_end');
        $hasDeliveryPreferencesMaxDailyNotifications = Schema::hasColumn('delivery_preferences', 'max_daily_notifications');
        $hasDeliveryPreferencesLanguage = Schema::hasColumn('delivery_preferences', 'language');
        $hasDeliveryPreferencesTimezone = Schema::hasColumn('delivery_preferences', 'timezone');
        $hasDeliveryPreferencesDeletedAt = Schema::hasColumn('delivery_preferences', 'deleted_at');
        Schema::table('delivery_preferences', function (Blueprint $table) use ($hasDeliveryPreferencesQuietHoursStart, $hasDeliveryPreferencesQuietHoursEnd, $hasDeliveryPreferencesMaxDailyNotifications, $hasDeliveryPreferencesLanguage, $hasDeliveryPreferencesTimezone, $hasDeliveryPreferencesDeletedAt) {
            $columns = [];

            if ($hasDeliveryPreferencesQuietHoursStart) {
                $columns[] = 'quiet_hours_start';
            }
            if ($hasDeliveryPreferencesQuietHoursEnd) {
                $columns[] = 'quiet_hours_end';
            }
            if ($hasDeliveryPreferencesMaxDailyNotifications) {
                $columns[] = 'max_daily_notifications';
            }
            if ($hasDeliveryPreferencesLanguage) {
                $columns[] = 'language';
            }
            if ($hasDeliveryPreferencesTimezone) {
                $columns[] = 'timezone';
            }
            if ($hasDeliveryPreferencesDeletedAt) {
                $columns[] = 'deleted_at';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

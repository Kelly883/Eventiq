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
        Schema::table('delivery_preferences', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('delivery_preferences', 'quiet_hours_start')) {
                $columns[] = 'quiet_hours_start';
            }
            if (Schema::hasColumn('delivery_preferences', 'quiet_hours_end')) {
                $columns[] = 'quiet_hours_end';
            }
            if (Schema::hasColumn('delivery_preferences', 'max_daily_notifications')) {
                $columns[] = 'max_daily_notifications';
            }
            if (Schema::hasColumn('delivery_preferences', 'language')) {
                $columns[] = 'language';
            }
            if (Schema::hasColumn('delivery_preferences', 'timezone')) {
                $columns[] = 'timezone';
            }
            if (Schema::hasColumn('delivery_preferences', 'deleted_at')) {
                $columns[] = 'deleted_at';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

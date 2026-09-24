<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Device tokens: add hash + encrypted columns for secure at-rest storage
        Schema::table('push_notification_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('push_notification_devices', 'token_hash')) {
                $table->string('token_hash', 64)->unique()->after('token');
            }
            if (!Schema::hasColumn('push_notification_devices', 'token_encrypted')) {
                $table->text('token_encrypted')->after('token_hash');
            }
        });

        // Users: add devices_count for fast "has any devices" checks
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'push_devices_count')) {
                $table->integer('push_devices_count')->default(0);
            }
        });

        // Push templates: add is_system_template flag
        Schema::table('push_notification_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('push_notification_templates', 'is_system_template')) {
                $table->boolean('is_system_template')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_devices', function (Blueprint $table) {
            $table->dropColumn(['token_hash', 'token_encrypted']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('push_devices_count');
        });
        Schema::table('push_notification_templates', function (Blueprint $table) {
            $table->dropColumn('is_system_template');
        });
    }
};

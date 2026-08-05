<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_preferences')) {
            return;
        }

        if (! Schema::hasColumn('delivery_preferences', 'push_notifications_enabled')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('push_notifications_enabled')->default(false)->after('promotional_offers');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'push_order_confirmation')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('push_order_confirmation')->default(false)->after('push_notifications_enabled');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'push_event_reminder')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('push_event_reminder')->default(false)->after('push_order_confirmation');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'push_checkin_alert')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('push_checkin_alert')->default(false)->after('push_event_reminder');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'push_promotional_offers')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('push_promotional_offers')->default(false)->after('push_checkin_alert');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('delivery_preferences')) {
            return;
        }

        foreach ([
            'push_notifications_enabled',
            'push_order_confirmation',
            'push_event_reminder',
            'push_checkin_alert',
            'push_promotional_offers',
        ] as $column) {
            if (Schema::hasColumn('delivery_preferences', $column)) {
                Schema::table('delivery_preferences', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
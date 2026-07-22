<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_preferences', function (Blueprint $table) {
            $table->boolean('push_notifications_enabled')->default(false)->after('promotional_offers');
            $table->boolean('push_order_confirmation')->default(false)->after('push_notifications_enabled');
            $table->boolean('push_event_reminder')->default(false)->after('push_order_confirmation');
            $table->boolean('push_checkin_alert')->default(false)->after('push_event_reminder');
            $table->boolean('push_promotional_offers')->default(false)->after('push_checkin_alert');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'push_notifications_enabled',
                'push_order_confirmation',
                'push_event_reminder',
                'push_checkin_alert',
                'push_promotional_offers'
            ]);
        });
    }
};
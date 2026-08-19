<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notification_devices', function (Blueprint $table) {
            $table->boolean('offline_enabled')->default(false)->after('device_type');
            $table->timestamp('last_sync_at')->nullable()->after('offline_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_devices', function (Blueprint $table) {
            $table->dropColumn(['offline_enabled', 'last_sync_at']);
        });
    }
};

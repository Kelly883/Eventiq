<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notification_devices', function (Blueprint $table) {
            if (Schema::hasColumn('push_notification_devices', 'token')) {
                $table->dropUnique('push_notification_devices_token_unique');
                $table->dropIndex('push_notification_devices_token_index');
                $table->string('token')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_devices', function (Blueprint $table) {
            $table->string('token')->nullable(false)->change();
            $table->unique('token', 'push_notification_devices_token_unique');
            $table->index('token', 'push_notification_devices_token_index');
        });
    }
};

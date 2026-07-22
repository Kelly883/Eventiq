<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notification_devices', function (Blueprint $table) {
            $table->uuid('id')->primary()->change();
            $table->string('token')->unique()->after('id');
            $table->string('provider')->after('user_id');
            $table->enum('device_type', ['web', 'ios', 'android'])->after('provider');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_devices', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->id()->change();
            $table->dropColumn(['token', 'provider', 'device_type']);
        });
    }
};
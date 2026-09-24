<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notification_devices', function (Blueprint $table) {
            $columns = [
                'device_name' => fn($t) => $t->string('device_name')->nullable(),
                'model' => fn($t) => $t->string('model')->nullable(),
                'app_version' => fn($t) => $t->string('app_version', 50)->nullable(),
                'os_version' => fn($t) => $t->string('os_version', 50)->nullable(),
                'locale' => fn($t) => $t->string('locale', 10)->nullable(),
                'timezone' => fn($t) => $t->string('timezone', 100)->nullable(),
                'offline_enabled' => fn($t) => $t->boolean('offline_enabled')->default(false),
                'last_sync_at' => fn($t) => $t->timestamp('last_sync_at')->nullable(),
                'last_used_at' => fn($t) => $t->timestamp('last_used_at')->nullable(),
                'last_error' => fn($t) => $t->text('last_error')->nullable(),
                'error_count' => fn($t) => $t->integer('error_count')->default(0),
            ];

            foreach ($columns as $name => $definition) {
                if (!Schema::hasColumn('push_notification_devices', $name)) {
                    $definition($table);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_devices', function (Blueprint $table) {
            $table->dropColumn([
                'device_name',
                'model',
                'app_version',
                'os_version',
                'locale',
                'timezone',
                'offline_enabled',
                'last_sync_at',
                'last_used_at',
                'last_error',
                'error_count',
            ]);
        });
    }
};

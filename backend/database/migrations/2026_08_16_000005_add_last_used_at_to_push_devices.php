<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('push_notification_devices') && ! Schema::hasColumn('push_notification_devices', 'last_used_at')) {
            DB::statement('ALTER TABLE push_notification_devices ADD COLUMN last_used_at DATETIME NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('push_notification_devices') && Schema::hasColumn('push_notification_devices', 'last_used_at')) {
            DB::statement('ALTER TABLE push_notification_devices DROP COLUMN last_used_at');
        }
    }
};

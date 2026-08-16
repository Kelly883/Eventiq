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
        if (! Schema::hasTable('push_notification_templates')) {
            return;
        }

        if (! Schema::hasColumn('push_notification_templates', 'priority')) {
            DB::statement('ALTER TABLE push_notification_templates ADD COLUMN priority VARCHAR(20) DEFAULT "normal"');
        }

        if (! Schema::hasColumn('push_notification_templates', 'badge')) {
            DB::statement('ALTER TABLE push_notification_templates ADD COLUMN badge INTEGER DEFAULT 1');
        }

        if (! Schema::hasColumn('push_notification_templates', 'sound')) {
            DB::statement('ALTER TABLE push_notification_templates ADD COLUMN sound VARCHAR(100) DEFAULT "default"');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('push_notification_templates')) {
            return;
        }

        foreach (['sound', 'badge', 'priority'] as $column) {
            if (Schema::hasColumn('push_notification_templates', $column)) {
                DB::statement('ALTER TABLE push_notification_templates DROP COLUMN ' . $column);
            }
        }
    }
};

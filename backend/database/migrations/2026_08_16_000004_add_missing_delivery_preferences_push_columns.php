<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The earlier migration 2026_07_22_073002 attempted to add push
     * notification preference columns to delivery_preferences, but on
     * SQLite the ALTER TABLE ... AFTER syntax caused the operation to
     * be skipped. This migration ensures the columns exist on all
     * platforms.
     */
    public function up(): void
    {
        if (! Schema::hasTable('delivery_preferences')) {
            return;
        }

        $columns = [
            'push_notifications_enabled' => 'BOOLEAN DEFAULT 0',
            'push_order_confirmation' => 'BOOLEAN DEFAULT 0',
            'push_event_reminder' => 'BOOLEAN DEFAULT 0',
            'push_checkin_alert' => 'BOOLEAN DEFAULT 0',
            'push_promotional_offers' => 'BOOLEAN DEFAULT 0',
        ];

        foreach ($columns as $column => $definition) {
            $exists = DB::select('PRAGMA table_info(delivery_preferences)');
            $found = false;
            foreach ($exists as $col) {
                if ($col->name === $column) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                DB::statement('ALTER TABLE delivery_preferences ADD COLUMN ' . $column . ' ' . $definition);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('delivery_preferences')) {
            return;
        }

        foreach ([
            'push_promotional_offers',
            'push_checkin_alert',
            'push_event_reminder',
            'push_order_confirmation',
            'push_notifications_enabled',
        ] as $column) {
            $exists = DB::select('PRAGMA table_info(delivery_preferences)');
            $found = false;
            foreach ($exists as $col) {
                if ($col->name === $column) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                DB::statement('ALTER TABLE delivery_preferences DROP COLUMN ' . $column);
            }
        }
    }
};

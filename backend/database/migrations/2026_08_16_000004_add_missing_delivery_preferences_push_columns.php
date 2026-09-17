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

        $driver = DB::getDriverName();

        $columns = [
            'push_notifications_enabled' => 'BOOLEAN DEFAULT 0',
            'push_order_confirmation' => 'BOOLEAN DEFAULT 0',
            'push_event_reminder' => 'BOOLEAN DEFAULT 0',
            'push_checkin_alert' => 'BOOLEAN DEFAULT 0',
            'push_promotional_offers' => 'BOOLEAN DEFAULT 0',
        ];

        foreach ($columns as $column => $definition) {
            $exists = false;

            if ($driver === 'sqlite') {
                $rows = DB::select('PRAGMA table_info(delivery_preferences)');
                foreach ($rows as $col) {
                    if ($col->name === $column) {
                        $exists = true;
                        break;
                    }
                }
            } else {
                $row = DB::selectOne(
                    'SELECT column_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?',
                    ['delivery_preferences', $column]
                );
                $exists = $row !== null;
            }

            if (! $exists) {
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

        $driver = DB::getDriverName();

        foreach ([
            'push_promotional_offers',
            'push_checkin_alert',
            'push_event_reminder',
            'push_order_confirmation',
            'push_notifications_enabled',
        ] as $column) {
            $exists = false;

            if ($driver === 'sqlite') {
                $rows = DB::select('PRAGMA table_info(delivery_preferences)');
                foreach ($rows as $col) {
                    if ($col->name === $column) {
                        $exists = true;
                        break;
                    }
                }
            } else {
                $row = DB::selectOne(
                    'SELECT column_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?',
                    ['delivery_preferences', $column]
                );
                $exists = $row !== null;
            }

            if ($exists) {
                DB::statement('ALTER TABLE delivery_preferences DROP COLUMN ' . $column);
            }
        }
    }
};

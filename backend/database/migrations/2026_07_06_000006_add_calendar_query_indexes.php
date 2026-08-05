<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                $table->index(['status', 'event_date'], 'events_status_event_date_index');
                $table->index(['status', 'category_id'], 'events_status_category_id_index');
                $table->index('event_date', 'events_event_date_index');
                $table->index('status', 'events_status_index');
            });
        }

        if (Schema::hasTable('ticket_inventory')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->index(['event_id', 'ticket_tier_id'], 'ticket_inventory_event_ticket_tier_index');
                $table->index('event_id', 'ticket_inventory_event_id_index');
            });
        }

        if (Schema::hasTable('pricing_windows')) {
            Schema::table('pricing_windows', function (Blueprint $table) {
                $table->index(['event_id', 'ticket_tier_id'], 'pricing_windows_event_ticket_tier_index');
                $table->index('event_id', 'pricing_windows_event_id_index');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropIndex('events_status_event_date_index');
                $table->dropIndex('events_status_category_id_index');
                $table->dropIndex('events_event_date_index');
                $table->dropIndex('events_status_index');
            });
        }

        if (Schema::hasTable('ticket_inventory')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->dropIndex('ticket_inventory_event_ticket_tier_index');
                $table->dropIndex('ticket_inventory_event_id_index');
            });
        }

        if (Schema::hasTable('pricing_windows')) {
            Schema::table('pricing_windows', function (Blueprint $table) {
                $table->dropIndex('pricing_windows_event_ticket_tier_index');
                $table->dropIndex('pricing_windows_event_id_index');
            });
        }
    }
};

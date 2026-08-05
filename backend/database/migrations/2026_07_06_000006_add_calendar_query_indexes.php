<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('events')) {
            if (Schema::hasColumn('events', 'status') && Schema::hasColumn('events', 'event_date')) {
                Schema::table('events', function (Blueprint $table) {
                    $table->index(['status', 'event_date'], 'events_status_event_date_index');
                });
            }

            if (Schema::hasColumn('events', 'status') && Schema::hasColumn('events', 'category_id')) {
                Schema::table('events', function (Blueprint $table) {
                    $table->index(['status', 'category_id'], 'events_status_category_id_index');
                });
            }

            if (Schema::hasColumn('events', 'event_date')) {
                Schema::table('events', function (Blueprint $table) {
                    $table->index('event_date', 'events_event_date_index');
                });
            }

            if (Schema::hasColumn('events', 'status')) {
                Schema::table('events', function (Blueprint $table) {
                    $table->index('status', 'events_status_index');
                });
            }
        }

        if (Schema::hasTable('ticket_inventory')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->index(['event_id', 'ticket_tier_id'], 'ticket_inventory_event_ticket_tier_index');
                $table->index('event_id', 'ticket_inventory_event_id_index');
            });
        }

        if (Schema::hasTable('pricing_windows')) {
            if (Schema::hasColumn('pricing_windows', 'event_id') && Schema::hasColumn('pricing_windows', 'ticket_tier_id')) {
                Schema::table('pricing_windows', function (Blueprint $table) {
                    $table->index(['event_id', 'ticket_tier_id'], 'pricing_windows_event_ticket_tier_index');
                });
            }

            if (Schema::hasColumn('pricing_windows', 'event_id')) {
                Schema::table('pricing_windows', function (Blueprint $table) {
                    $table->index('event_id', 'pricing_windows_event_id_index');
                });
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                try {
                    $table->dropIndex('events_status_event_date_index');
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropIndex('events_status_category_id_index');
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropIndex('events_event_date_index');
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropIndex('events_status_index');
                } catch (\Throwable $e) {
                }
            });
        }

        if (Schema::hasTable('ticket_inventory')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                try {
                    $table->dropIndex('ticket_inventory_event_ticket_tier_index');
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropIndex('ticket_inventory_event_id_index');
                } catch (\Throwable $e) {
                }
            });
        }

        if (Schema::hasTable('pricing_windows')) {
            Schema::table('pricing_windows', function (Blueprint $table) {
                try {
                    $table->dropIndex('pricing_windows_event_ticket_tier_index');
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropIndex('pricing_windows_event_id_index');
                } catch (\Throwable $e) {
                }
            });
        }
    }
};

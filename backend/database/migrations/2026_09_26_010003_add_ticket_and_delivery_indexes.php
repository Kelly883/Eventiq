<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasIndex('tickets', 'tickets_user_id_index')) {
                $table->index('user_id', 'tickets_user_id_index');
            }
            if (!Schema::hasIndex('tickets', 'tickets_ticket_id_index')) {
                $table->index('ticket_id', 'tickets_ticket_id_index');
            }
            if (!Schema::hasIndex('tickets', 'tickets_event_id_index')) {
                $table->index('event_id', 'tickets_event_id_index');
            }

            if (!Schema::hasIndex('tickets', 'tickets_user_id_created_at_index')) {
                $table->index(['user_id', 'created_at'], 'tickets_user_id_created_at_index');
            }

            if (!Schema::hasIndex('tickets', 'tickets_user_id_checked_in_index')) {
                $table->index(['user_id', 'checked_in'], 'tickets_user_id_checked_in_index');
            }
        });

        if (Schema::hasTable('delivery_events')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                if (!Schema::hasIndex('delivery_events', 'delivery_events_ticket_id_created_at_index')) {
                    $table->index(['ticket_id', 'created_at'], 'delivery_events_ticket_id_created_at_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            try {
                $table->dropIndex('tickets_user_id_index');
            } catch (\Throwable $e) {
                // Index may not exist
            }
            try {
                $table->dropIndex('tickets_ticket_id_index');
            } catch (\Throwable $e) {
                // Index may not exist
            }

            try {
                $table->dropIndex('tickets_user_id_created_at_index');
            } catch (\Throwable $e) {
                // Index may not exist
            }

            try {
                $table->dropIndex('tickets_user_id_checked_in_index');
            } catch (\Throwable $e) {
                // Index may not exist
            }
        });

        if (Schema::hasTable('delivery_events')) {
            Schema::table('delivery_events', function (Blueprint $table) {
                try {
                    $table->dropIndex('delivery_events_ticket_id_created_at_index');
                } catch (\Throwable $e) {
                    // Index may not exist
                }
            });
        }
    }
};

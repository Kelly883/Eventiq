<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('user_id', 'tickets_user_id_index');
            $table->index('ticket_id', 'tickets_ticket_id_index');
            $table->index('event_id', 'tickets_event_id_index');
            $table->index(['user_id', 'created_at'], 'tickets_user_id_created_at_index');
            $table->index(['user_id', 'checked_in'], 'tickets_user_id_checked_in_index');
        });

        Schema::table('delivery_events', function (Blueprint $table) {
            $table->index(['ticket_id', 'created_at'], 'delivery_events_ticket_id_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_user_id_index');
            $table->dropIndex('tickets_ticket_id_index');
            $table->dropIndex('tickets_event_id_index');
            $table->dropIndex('tickets_user_id_created_at_index');
            $table->dropIndex('tickets_user_id_checked_in_index');
        });

        Schema::table('delivery_events', function (Blueprint $table) {
            $table->dropIndex('delivery_events_ticket_id_created_at_index');
        });
    }
};

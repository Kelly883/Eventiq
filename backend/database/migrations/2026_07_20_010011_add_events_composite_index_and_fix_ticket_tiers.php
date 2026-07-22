<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: The composite index (organizer_id, status) is already created
        // by migration 2026_07_20_010009_update_events_table_for_step58.
        // This migration originally tried to create it again (causing a crash).
        // We only need to handle the ticket_tiers.capacity drop here.

        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropColumn('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->nullable()->after('price');
        });
    }
};
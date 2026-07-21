<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index(['organizer_id', 'status']);
        });

        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropColumn('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['events_organizer_id_status_index']);
        });

        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->nullable()->after('price');
        });
    }
};
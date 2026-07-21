<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            // Add sold_count for quick availability checks
            $table->unsignedInteger('sold_count')->default(0)->after('quantity');
            
            // Add available_count as virtual/generated column for available tickets
            // Note: For MySQL 5.7+ or PostgreSQL, you could use a generated column
            // For SQLite compatibility, we'll just store it as a regular column
            $table->unsignedInteger('available_count')->default(0)->after('sold_count');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropColumn(['sold_count', 'available_count']);
        });
    }
};
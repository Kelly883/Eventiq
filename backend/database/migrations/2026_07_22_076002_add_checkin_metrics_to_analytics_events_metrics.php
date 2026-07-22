<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('analytics_events_metrics', function (Blueprint $table) {
            // Add check-in tracking fields for the check-in system
            $table->integer('total_checked_in')->default(0)->after('total_tickets_sold');
            $table->decimal('check_in_rate', 5, 2)->default(0)->after('total_checked_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_events_metrics', function (Blueprint $table) {
            $table->dropColumn(['total_checked_in', 'check_in_rate']);
        });
    }
};

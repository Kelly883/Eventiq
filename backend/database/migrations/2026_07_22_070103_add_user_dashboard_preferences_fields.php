<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_dashboard_preferences', function (Blueprint $table) {
            $table->string('user_id')->after('id');
            $table->string('default_ticket_filter')->default('all')->after('user_id');
            $table->string('default_date_range')->default('30days')->after('default_ticket_filter');
            $table->boolean('show_recommendations')->default(true)->after('default_date_range');
            $table->boolean('show_activity_feed')->default(true)->after('show_recommendations');
            $table->boolean('auto_refresh_enabled')->default(true)->after('show_activity_feed');

            $table->unique('user_id');
            $table->index('user_id');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_dashboard_preferences', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn([
                'user_id',
                'default_ticket_filter',
                'default_date_range',
                'show_recommendations',
                'show_activity_feed',
                'auto_refresh_enabled',
            ]);
        });
    }
};
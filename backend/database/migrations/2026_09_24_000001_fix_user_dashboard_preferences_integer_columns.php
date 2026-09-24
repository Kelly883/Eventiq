<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recreate the table with proper INTEGER type for boolean columns on SQLite
        Schema::dropIfExists('user_dashboard_preferences');

        Schema::create('user_dashboard_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('default_ticket_filter')->default('all');
            $table->string('default_date_range')->default('30days');
            $table->integer('show_recommendations')->default(1);
            $table->integer('show_activity_feed')->default(1);
            $table->integer('auto_refresh_enabled')->default(1);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dashboard_preferences');

        Schema::create('user_dashboard_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('default_ticket_filter')->default('all');
            $table->string('default_date_range')->default('30days');
            $table->boolean('show_recommendations')->default(true);
            $table->boolean('show_activity_feed')->default(true);
            $table->boolean('auto_refresh_enabled')->default(true);
            $table->timestamps();

            $table->unique('user_id');
            $table->index('user_id');
        });
    }
};

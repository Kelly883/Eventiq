<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_dashboard_preferences')) {
            return;
        }

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

    public function down(): void
    {
        Schema::dropIfExists('user_dashboard_preferences');
    }
};
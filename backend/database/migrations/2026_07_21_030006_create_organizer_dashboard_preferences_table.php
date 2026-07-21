<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizer_dashboard_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('organizer_id')->constrained('organizers')->onDelete('cascade');
            $table->string('default_event_filter', 20)->default('all');
            $table->string('default_date_range', 20)->default('30days');
            $table->foreignId('expanded_event_id')->nullable()->constrained('events')->onDelete('set null');
            $table->boolean('show_activity_feed')->default(true);
            $table->boolean('auto_refresh_enabled')->default(true);
            $table->timestamps();

            // Unique index for one preferences record per organizer
            $table->unique('organizer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_dashboard_preferences');
    }
};
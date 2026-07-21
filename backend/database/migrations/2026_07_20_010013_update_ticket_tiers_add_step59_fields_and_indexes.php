<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->text('benefits_description')->nullable()->after('quantity');
            $table->string('tier_image_url')->nullable()->after('benefits_description');
            $table->unsignedInteger('max_per_customer')->nullable()->after('early_bird_end_date');

            $table->index(['event_id', 'sales_start_date']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropIndex(['ticket_tiers_event_id_sales_start_date_index']);
            $table->dropIndex(['ticket_tiers_created_at_index']);

            $table->dropColumn(['benefits_description', 'tier_image_url', 'max_per_customer']);
        });
    }
};
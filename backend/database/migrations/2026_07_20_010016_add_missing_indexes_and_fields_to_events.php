<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Missing indexes for performance
            $table->index('deleted_at');
            $table->index('start_datetime');
            $table->index('organizer_id');

            // Missing fields for a real event system
            $table->string('timezone', 50)->nullable()->after('end_datetime');
            $table->string('currency', 3)->default('NGN')->after('timezone');
            $table->string('slug', 255)->unique()->nullable()->after('title');
            $table->text('cancellation_reason')->nullable()->after('status');
            $table->unsignedInteger('max_tickets_per_order')->nullable()->after('capacity');
            $table->string('age_restriction', 10)->nullable()->after('max_tickets_per_order');
            $table->json('tags')->nullable()->after('age_restriction');
            $table->decimal('latitude', 10, 7)->nullable()->after('venue_address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['events_deleted_at_index']);
            $table->dropIndex(['events_start_datetime_index']);
            $table->dropIndex(['events_organizer_id_index']);

            $table->dropColumn([
                'timezone',
                'currency',
                'slug',
                'cancellation_reason',
                'max_tickets_per_order',
                'age_restriction',
                'tags',
                'latitude',
                'longitude',
            ]);
        });
    }
};
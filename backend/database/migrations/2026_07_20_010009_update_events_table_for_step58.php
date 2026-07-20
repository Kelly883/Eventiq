<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->renameColumn('location', 'venue_name');
            $table->string('venue_address')->nullable()->after('venue_name');
            $table->renameColumn('banner_path', 'banner_image_url');
            $table->unsignedInteger('capacity')->nullable(false)->change();
            $table->timestamp('deleted_at')->nullable()->after('updated_at');

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['events_user_id_index']);
            $table->dropIndex(['events_status_index']);

            $table->dropColumn(['user_id', 'venue_address', 'deleted_at']);

            $table->renameColumn('venue_name', 'location');
            $table->renameColumn('banner_image_url', 'banner_path');
            $table->unsignedInteger('capacity')->nullable()->change();
        });
    }
};
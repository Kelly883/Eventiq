<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            if (Schema::hasColumn('organizers', 'business_name')) {
                $table->dropColumn('business_name');
            }
            if (Schema::hasColumn('organizers', 'branding_color')) {
                $table->dropColumn('branding_color');
            }
            if (Schema::hasColumn('organizers', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
            if (Schema::hasColumn('organizers', 'website_url')) {
                $table->dropColumn('website_url');
            }
            if (Schema::hasColumn('organizers', 'social_links')) {
                $table->dropColumn('social_links');
            }
            if (Schema::hasColumn('organizers', 'privacy_settings')) {
                $table->dropColumn('privacy_settings');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->string('business_name')->nullable()->after('user_id');
            $table->string('branding_color', 7)->nullable()->after('bio');
            $table->string('logo_path')->nullable()->after('branding_color');
            $table->string('website_url')->nullable()->after('logo_path');
            $table->json('social_links')->nullable()->after('website_url');
            $table->json('privacy_settings')->nullable()->after('social_links');
        });
    }
};

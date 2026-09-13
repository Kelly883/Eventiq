<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasOrganizersBusinessName = Schema::hasColumn('organizers', 'business_name');
        $hasOrganizersBrandingColor = Schema::hasColumn('organizers', 'branding_color');
        $hasOrganizersLogoPath = Schema::hasColumn('organizers', 'logo_path');
        $hasOrganizersWebsiteUrl = Schema::hasColumn('organizers', 'website_url');
        $hasOrganizersSocialLinks = Schema::hasColumn('organizers', 'social_links');
        $hasOrganizersPrivacySettings = Schema::hasColumn('organizers', 'privacy_settings');
        Schema::table('organizers', function (Blueprint $table) use ($hasOrganizersBusinessName, $hasOrganizersBrandingColor, $hasOrganizersLogoPath, $hasOrganizersWebsiteUrl, $hasOrganizersSocialLinks, $hasOrganizersPrivacySettings) {
            if ($hasOrganizersBusinessName) {
                $table->dropColumn('business_name');
            }
            if ($hasOrganizersBrandingColor) {
                $table->dropColumn('branding_color');
            }
            if ($hasOrganizersLogoPath) {
                $table->dropColumn('logo_path');
            }
            if ($hasOrganizersWebsiteUrl) {
                $table->dropColumn('website_url');
            }
            if ($hasOrganizersSocialLinks) {
                $table->dropColumn('social_links');
            }
            if ($hasOrganizersPrivacySettings) {
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

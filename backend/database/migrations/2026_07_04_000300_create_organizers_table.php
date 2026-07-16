<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique();
            $table->string('business_name')->nullable();
            $table->text('bio')->nullable();
            $table->string('branding_color', 7)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('website_url')->nullable();
            $table->json('social_links')->nullable();
            $table->json('privacy_settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizers');
    }
};

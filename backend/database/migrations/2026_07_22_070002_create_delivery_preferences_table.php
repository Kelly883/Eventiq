<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_preferences')) {
            return;
        }

        Schema::create('delivery_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->boolean('email_enabled')->default(true);
            $table->string('email_address')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->boolean('sms_enabled')->default(false);
            $table->string('phone_number')->nullable();
            $table->boolean('phone_verified')->default(false);
            $table->boolean('dashboard_enabled')->default(true);
            $table->enum('primary_method', ['email', 'sms', 'dashboard'])->default('email');
            $table->enum('backup_method', ['email', 'sms', 'dashboard'])->nullable();
            $table->enum('delivery_timing', ['immediate', 'scheduled_1h_before', 'scheduled_24h_before'])->default('immediate');
            $table->boolean('receive_confirmation')->default(true);
            $table->boolean('receive_reminders')->default(true);
            $table->enum('language_preference', ['en', 'es', 'fr', 'de'])->default('en');
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_preferences');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_preferences', function (Blueprint $table) {
            $table->uuid('user_id')->unique()->after('id');
            $table->boolean('email_enabled')->default(true)->after('user_id');
            $table->string('email_address')->nullable()->after('email_enabled');
            $table->boolean('email_verified')->default(false)->after('email_address');
            $table->boolean('sms_enabled')->default(false)->after('email_verified');
            $table->string('phone_number')->nullable()->after('sms_enabled');
            $table->boolean('phone_verified')->default(false)->after('phone_number');
            $table->boolean('dashboard_enabled')->default(true)->after('phone_verified');
            $table->enum('primary_method', ['email', 'sms', 'dashboard'])->default('email')->after('dashboard_enabled');
            $table->enum('backup_method', ['email', 'sms', 'dashboard'])->nullable()->after('primary_method');
            $table->enum('delivery_timing', [
                'immediate',
                'scheduled_1h_before',
                'scheduled_24h_before'
            ])->default('immediate')->after('backup_method');
            $table->boolean('receive_confirmation')->default(true)->after('delivery_timing');
            $table->boolean('receive_reminders')->default(true)->after('receive_confirmation');
            $table->enum('language_preference', ['en', 'es', 'fr', 'de'])->default('en')->after('receive_reminders');
            $table->index('user_id');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_preferences', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn([
                'user_id',
                'email_enabled',
                'email_address',
                'email_verified',
                'sms_enabled',
                'phone_number',
                'phone_verified',
                'dashboard_enabled',
                'primary_method',
                'backup_method',
                'delivery_timing',
                'receive_confirmation',
                'receive_reminders',
                'language_preference',
            ]);
        });
    }
};
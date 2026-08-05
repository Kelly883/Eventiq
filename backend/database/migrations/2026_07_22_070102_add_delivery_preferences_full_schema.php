<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_preferences')) {
            return;
        }

        if (! Schema::hasColumn('delivery_preferences', 'user_id')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->uuid('user_id')->unique()->after('id');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'email_enabled')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('email_enabled')->default(true)->after('user_id');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'email_address')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->string('email_address')->nullable()->after('email_enabled');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'email_verified')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('email_verified')->default(false)->after('email_address');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'sms_enabled')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('sms_enabled')->default(false)->after('email_verified');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'phone_number')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->string('phone_number')->nullable()->after('sms_enabled');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'phone_verified')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('phone_verified')->default(false)->after('phone_number');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'dashboard_enabled')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('dashboard_enabled')->default(true)->after('phone_verified');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'primary_method')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->enum('primary_method', ['email', 'sms', 'dashboard'])->default('email')->after('dashboard_enabled');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'backup_method')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->enum('backup_method', ['email', 'sms', 'dashboard'])->nullable()->after('primary_method');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'delivery_timing')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->enum('delivery_timing', [
                    'immediate',
                    'scheduled_1h_before',
                    'scheduled_24h_before'
                ])->default('immediate')->after('backup_method');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'receive_confirmation')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('receive_confirmation')->default(true)->after('delivery_timing');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'receive_reminders')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('receive_reminders')->default(true)->after('receive_confirmation');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'language_preference')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->enum('language_preference', ['en', 'es', 'fr', 'de'])->default('en')->after('receive_reminders');
            });
        }

        try {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->index('user_id');
            });
        } catch (\Throwable $e) {
            // Index may already exist.
        }

        try {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        } catch (\Throwable $e) {
            // FK may already exist or be unsupported in current SQLite mode.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('delivery_preferences')) {
            return;
        }

        try {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Throwable $e) {
            // FK may not exist.
        }

        try {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
            });
        } catch (\Throwable $e) {
            // Index may not exist.
        }

        $dropColumns = [
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
        ];

        foreach ($dropColumns as $column) {
            if (Schema::hasColumn('delivery_preferences', $column)) {
                Schema::table('delivery_preferences', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
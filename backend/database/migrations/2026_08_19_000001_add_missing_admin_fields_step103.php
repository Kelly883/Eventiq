<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'suspension_reason')) {
                    $table->text('suspension_reason')->nullable()->after('status');
                }
                if (! Schema::hasColumn('users', 'suspension_date')) {
                    $table->timestamp('suspension_date')->nullable()->after('suspension_reason');
                }
            });
        }

        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                if (! Schema::hasColumn('events', 'flag_reason')) {
                    $table->text('flag_reason')->nullable()->after('status');
                }
                if (! Schema::hasColumn('events', 'flag_date')) {
                    $table->timestamp('flag_date')->nullable()->after('flag_reason');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'fraud_detection_method')) {
                    $table->string('fraud_detection_method')->nullable()->after('gateway_response_code');
                }
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('audit_logs', 'description')) {
                    $table->text('description')->nullable()->after('target_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'suspension_reason')) {
                    $table->dropColumn('suspension_reason');
                }
                if (Schema::hasColumn('users', 'suspension_date')) {
                    $table->dropColumn('suspension_date');
                }
            });
        }

        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                if (Schema::hasColumn('events', 'flag_reason')) {
                    $table->dropColumn('flag_reason');
                }
                if (Schema::hasColumn('events', 'flag_date')) {
                    $table->dropColumn('flag_date');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'fraud_detection_method')) {
                    $table->dropColumn('fraud_detection_method');
                }
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (Schema::hasColumn('audit_logs', 'description')) {
                    $table->dropColumn('description');
                }
            });
        }
    }
};

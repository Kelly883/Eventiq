<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            $hasUsersSuspensionReason = Schema::hasColumn('users', 'suspension_reason');
            $hasUsersSuspensionDate = Schema::hasColumn('users', 'suspension_date');
            Schema::table('users', function (Blueprint $table) use ($hasUsersSuspensionReason, $hasUsersSuspensionDate) {
                if (! $hasUsersSuspensionReason) {
                    $table->text('suspension_reason')->nullable()->after('status');
                }
                if (! $hasUsersSuspensionDate) {
                    $table->timestamp('suspension_date')->nullable()->after('suspension_reason');
                }
            });
        }

        if (Schema::hasTable('events')) {
            $hasEventsFlagReason = Schema::hasColumn('events', 'flag_reason');
            $hasEventsFlagDate = Schema::hasColumn('events', 'flag_date');
            Schema::table('events', function (Blueprint $table) use ($hasEventsFlagReason, $hasEventsFlagDate) {
                if (! $hasEventsFlagReason) {
                    $table->text('flag_reason')->nullable()->after('status');
                }
                if (! $hasEventsFlagDate) {
                    $table->timestamp('flag_date')->nullable()->after('flag_reason');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            $hasPaymentsFraudDetectionMethod = Schema::hasColumn('payments', 'fraud_detection_method');
            Schema::table('payments', function (Blueprint $table) use ($hasPaymentsFraudDetectionMethod) {
                if (! $hasPaymentsFraudDetectionMethod) {
                    $table->string('fraud_detection_method')->nullable()->after('gateway_response_code');
                }
            });
        }

        if (Schema::hasTable('audit_logs')) {
            $hasAuditLogsDescription = Schema::hasColumn('audit_logs', 'description');
            Schema::table('audit_logs', function (Blueprint $table) use ($hasAuditLogsDescription) {
                if (! $hasAuditLogsDescription) {
                    $table->text('description')->nullable()->after('target_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            $hasUsersSuspensionReason = Schema::hasColumn('users', 'suspension_reason');
            $hasUsersSuspensionDate = Schema::hasColumn('users', 'suspension_date');
            Schema::table('users', function (Blueprint $table) use ($hasUsersSuspensionReason, $hasUsersSuspensionDate) {
                if ($hasUsersSuspensionReason) {
                    $table->dropColumn('suspension_reason');
                }
                if ($hasUsersSuspensionDate) {
                    $table->dropColumn('suspension_date');
                }
            });
        }

        if (Schema::hasTable('events')) {
            $hasEventsFlagReason = Schema::hasColumn('events', 'flag_reason');
            $hasEventsFlagDate = Schema::hasColumn('events', 'flag_date');
            Schema::table('events', function (Blueprint $table) use ($hasEventsFlagReason, $hasEventsFlagDate) {
                if ($hasEventsFlagReason) {
                    $table->dropColumn('flag_reason');
                }
                if ($hasEventsFlagDate) {
                    $table->dropColumn('flag_date');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            $hasPaymentsFraudDetectionMethod = Schema::hasColumn('payments', 'fraud_detection_method');
            Schema::table('payments', function (Blueprint $table) use ($hasPaymentsFraudDetectionMethod) {
                if ($hasPaymentsFraudDetectionMethod) {
                    $table->dropColumn('fraud_detection_method');
                }
            });
        }

        if (Schema::hasTable('audit_logs')) {
            $hasAuditLogsDescription = Schema::hasColumn('audit_logs', 'description');
            Schema::table('audit_logs', function (Blueprint $table) use ($hasAuditLogsDescription) {
                if ($hasAuditLogsDescription) {
                    $table->dropColumn('description');
                }
            });
        }
    }
};

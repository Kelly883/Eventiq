<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds compliance and traceability fields for Step 71:
     * - payment_method, order_item_id to tickets for traceability
     * - ip_address, user_agent to audit_logs for security compliance
     */
    public function up(): void
    {
        $hasTicketsOrderItemId = Schema::hasColumn('tickets', 'order_item_id');
        $hasTicketsIdxTicketsOrderItemIndex = Schema::hasIndex('tickets', 'idx_tickets_order_item');
        $hasTicketsPaymentMethod = Schema::hasColumn('tickets', 'payment_method');
        Schema::table('tickets', function (Blueprint $table) use ($hasTicketsOrderItemId, $hasTicketsIdxTicketsOrderItemIndex, $hasTicketsPaymentMethod) {
            // Add order_item_id for traceability
            if (!$hasTicketsOrderItemId) {
                $table->uuid('order_item_id')->nullable()->after('order_id')
                      ->comment('Links to order_items for detailed purchase traceability');
                
                // Add index for order item lookups
                try {
                    if (!$hasTicketsIdxTicketsOrderItemIndex) {
                        $table->index('order_item_id', 'idx_tickets_order_item');
                    }
                } catch (\Exception $e) {
                    // Index may already exist
                }
            }

            // Add payment_method for revenue tracking and analytics
            if (!$hasTicketsPaymentMethod) {
                $table->string('payment_method', 50)->nullable()->after('payment_method')
                      ->comment('Payment method used: card, bank_transfer, mobile_money, etc');
            }
        });

        $hasAuditLogsIpAddress = Schema::hasColumn('audit_logs', 'ip_address');
        $hasAuditLogsUserAgent = Schema::hasColumn('audit_logs', 'user_agent');
        $hasAuditLogsIdxAuditLogsIpIndex = Schema::hasIndex('audit_logs', 'idx_audit_logs_ip');
        Schema::table('audit_logs', function (Blueprint $table) use ($hasAuditLogsIpAddress, $hasAuditLogsUserAgent, $hasAuditLogsIdxAuditLogsIpIndex) {
            // Add ip_address for security compliance
            if (!$hasAuditLogsIpAddress) {
                $table->string('ip_address', 45)->nullable()->after('action')
                      ->comment('IP address of user performing action');
            }

            // Add user_agent for debugging and forensics
            if (!$hasAuditLogsUserAgent) {
                $table->string('user_agent', 500)->nullable()->after('ip_address')
                      ->comment('User agent string for debugging');
            }

            // Add index for IP-based lookups (compliance investigations)
            try {
                if (!$hasAuditLogsIdxAuditLogsIpIndex) {
                    $table->index('ip_address', 'idx_audit_logs_ip');
                }
            } catch (\Exception $e) {
                // Index may already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasAuditLogsUserAgent = Schema::hasColumn('audit_logs', 'user_agent');
        $hasAuditLogsIpAddress = Schema::hasColumn('audit_logs', 'ip_address');
        Schema::table('audit_logs', function (Blueprint $table) use ($hasAuditLogsUserAgent, $hasAuditLogsIpAddress) {
            // Drop indexes
            try {
                $table->dropIndex('idx_audit_logs_ip');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop columns
            if ($hasAuditLogsUserAgent) {
                $table->dropColumn('user_agent');
            }

            if ($hasAuditLogsIpAddress) {
                $table->dropColumn('ip_address');
            }
        });

        $hasTicketsPaymentMethod = Schema::hasColumn('tickets', 'payment_method');
        $hasTicketsOrderItemId = Schema::hasColumn('tickets', 'order_item_id');
        Schema::table('tickets', function (Blueprint $table) use ($hasTicketsPaymentMethod, $hasTicketsOrderItemId) {
            // Drop indexes
            try {
                $table->dropIndex('idx_tickets_order_item');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop columns
            if ($hasTicketsPaymentMethod) {
                $table->dropColumn('payment_method');
            }

            if ($hasTicketsOrderItemId) {
                $table->dropColumn('order_item_id');
            }
        });
    }
};

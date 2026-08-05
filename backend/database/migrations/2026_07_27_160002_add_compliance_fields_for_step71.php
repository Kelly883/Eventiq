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
        Schema::table('tickets', function (Blueprint $table) {
            // Add order_item_id for traceability
            if (!Schema::hasColumn('tickets', 'order_item_id')) {
                $table->uuid('order_item_id')->nullable()->after('order_id')
                      ->comment('Links to order_items for detailed purchase traceability');
                
                // Add index for order item lookups
                try {
                    if (!Schema::hasIndex('tickets', 'idx_tickets_order_item')) {
                        $table->index('order_item_id', 'idx_tickets_order_item');
                    }
                } catch (\Exception $e) {
                    // Index may already exist
                }
            }

            // Add payment_method for revenue tracking and analytics
            if (!Schema::hasColumn('tickets', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('payment_method')
                      ->comment('Payment method used: card, bank_transfer, mobile_money, etc');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            // Add ip_address for security compliance
            if (!Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('action')
                      ->comment('IP address of user performing action');
            }

            // Add user_agent for debugging and forensics
            if (!Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->string('user_agent', 500)->nullable()->after('ip_address')
                      ->comment('User agent string for debugging');
            }

            // Add index for IP-based lookups (compliance investigations)
            try {
                if (!Schema::hasIndex('audit_logs', 'idx_audit_logs_ip')) {
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
        Schema::table('audit_logs', function (Blueprint $table) {
            // Drop indexes
            try {
                $table->dropIndex('idx_audit_logs_ip');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop columns
            if (Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->dropColumn('user_agent');
            }

            if (Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->dropColumn('ip_address');
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            // Drop indexes
            try {
                $table->dropIndex('idx_tickets_order_item');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop columns
            if (Schema::hasColumn('tickets', 'payment_method')) {
                $table->dropColumn('payment_method');
            }

            if (Schema::hasColumn('tickets', 'order_item_id')) {
                $table->dropColumn('order_item_id');
            }
        });
    }
};

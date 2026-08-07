<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing indexes and columns for fraud analysis dashboard.
     *
     * 1. Composite index for common dashboard drill-down
     * 2. Index for user_email denormalized column
     * 3. Index for archived records queries
     * 4. Missing analysis columns: user_email, order_status, device_type, proxy_vpn_detected
     */
    public function up(): void
    {
        // Add columns first
        Schema::table('fraud_events', function (Blueprint $table) {
            // === Add missing analysis columns first ===
            // User email for dashboard display without JOIN
            if (!Schema::hasColumn('fraud_events', 'user_email')) {
                $table->string('user_email', 255)->nullable()->after('user_id')
                      ->comment('Denormalized user email for dashboard queries');
            }

            // Order status snapshot at time of fraud detection
            if (!Schema::hasColumn('fraud_events', 'order_status')) {
                $table->string('order_status', 50)->nullable()->after('order_total')
                      ->comment('Order status when fraud was detected: pending, paid, cancelled, refunded');
            }

            // Device type extracted from user agent
            if (!Schema::hasColumn('fraud_events', 'device_type')) {
                $table->string('device_type', 50)->nullable()->after('device_fingerprint')
                      ->comment('Device type: mobile, desktop, tablet, bot');
            }

            // Proxy/VPN detection
            if (!Schema::hasColumn('fraud_events', 'proxy_vpn_detected')) {
                $table->boolean('proxy_vpn_detected')->nullable()->after('device_type')
                      ->comment('Whether proxy or VPN was detected');
            }

            // IP reputation score (1-100)
            if (!Schema::hasColumn('fraud_events', 'ip_reputation_score')) {
                $table->integer('ip_reputation_score')->nullable()->after('proxy_vpn_detected')
                      ->comment('Third-party IP risk score (1-100)');
            }

            // Account age in days at time of fraud
            if (!Schema::hasColumn('fraud_events', 'account_age_days')) {
                $table->integer('account_age_days')->nullable()->after('ip_reputation_score')
                      ->comment('User account age in days when fraud occurred');
            }
        });

        // Then add indexes in a separate statement to ensure they're created
        Schema::enableForeignKeyConstraints();
        
        try {
            DB::statement('CREATE INDEX idx_fraud_risk_status_created ON fraud_events(risk_level, status, created_at)');
        } catch (\Exception $e) {}
        
        try {
            DB::statement('CREATE INDEX idx_fraud_user_email ON fraud_events(user_email)');
        } catch (\Exception $e) {}
        
        try {
            DB::statement('CREATE INDEX idx_fraud_archived_created ON fraud_events(is_archived, created_at)');
        } catch (\Exception $e) {}
        
        try {
            DB::statement('CREATE INDEX idx_fraud_detection_risk ON fraud_events(detection_method, risk_level)');
        } catch (\Exception $e) {}
        
        try {
            DB::statement('CREATE INDEX idx_fraud_proxy_vpn ON fraud_events(proxy_vpn_detected)');
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_fraud_risk_status_created');
            $table->dropIndex('idx_fraud_user_email');
            $table->dropIndex('idx_fraud_archived_created');
            $table->dropIndex('idx_fraud_detection_risk');
            $table->dropIndex('idx_fraud_proxy_vpn');

            // Drop columns
            $table->dropColumn([
                'user_email',
                'order_status',
                'device_type',
                'proxy_vpn_detected',
                'ip_reputation_score',
                'account_age_days',
            ]);
        });
    }
};
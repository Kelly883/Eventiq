<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('fraud_events')) {
            return;
        }

        Schema::create('fraud_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('user_id');
            $table->enum('event_type', [
                'duplicate_ticket_attempt',
                'velocity_check_failed',
                'payment_pattern_suspicious',
                'device_fingerprint_mismatch',
                'geolocation_anomaly',
                'card_testing',
                'high_risk_payment_method'
            ]);
            $table->decimal('risk_score', 5, 2);
            $table->enum('risk_level', ['low', 'medium', 'high']);
            $table->enum('detection_method', [
                'sift_science',
                'stripe_radar',
                'duplicate_detection',
                'velocity_check',
                'rule_based'
            ]);
            $table->json('fraud_factors')->nullable()->comment('duplicateTicketDetected, velocityCheckFailed, paymentPatternSuspicious, deviceFingerprintMismatch, geolocationAnomaly, cardTestingPattern, highRiskPaymentMethod');
            $table->json('payment_details')->nullable()->comment('cardLast4, issuer, country, cardFingerprint');
            $table->json('velocity_metrics')->nullable()->comment('ordersIn24h, totalSpendIn24h, averageOrderValue, ordersInLastHour');
            $table->json('device_info')->nullable()->comment('ipAddress, userAgent, deviceFingerprint, country, city');
            $table->json('duplicate_ticket_info')->nullable()->comment('matchingTicketIds[], matchingQRCodes[], matchingEventIds[]');
            $table->enum('status', [
                'flagged',
                'reviewed',
                'approved',
                'rejected',
                'auto_blocked'
            ]);
            $table->uuid('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            
            // Composite indexes for fast queries
            $table->index(['user_id', 'created_at'], 'idx_fraud_user_created');
            $table->index(['status', 'created_at'], 'idx_fraud_status_created');
            $table->index(['risk_level', 'created_at'], 'idx_fraud_risk_created');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fraud_events');
    }
};
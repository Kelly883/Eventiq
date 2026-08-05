<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old table created by the conflicting migration (071002)
        Schema::dropIfExists('fraud_events');

        Schema::create('fraud_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Foreign keys to core entities
            $table->uuid('order_id');
            $table->uuid('user_id');
            
            // Checkin-specific fraud (from the 071002 schema)
            $table->uuid('ticket_id')->nullable();
            $table->uuid('event_id')->nullable();
            
            // Fraud type classification
            $table->enum('event_type', [
                'duplicate_ticket_attempt',
                'velocity_check_failed',
                'payment_pattern_suspicious',
                'device_fingerprint_mismatch',
                'geolocation_anomaly',
                'card_testing',
                'high_risk_payment_method',
                'duplicate_checkin',
                'invalid_qr',
                'manual_override'
            ]);
            
            // Risk scoring
            $table->decimal('risk_score', 5, 2);
            $table->enum('risk_level', ['low', 'medium', 'high']);
            $table->enum('detection_method', [
                'sift_science',
                'stripe_radar',
                'duplicate_detection',
                'velocity_check',
                'rule_based',
                'qr_validation',
                'manual_review'
            ]);
            
            // JSON payloads for rich fraud context
            $table->json('fraud_factors')->nullable()->comment('List of specific fraud indicators that triggered this event');
            $table->json('payment_details')->nullable()->comment('Card last4, issuer, country, cardFingerprint');
            $table->json('velocity_metrics')->nullable()->comment('Orders in 24h, total spend, avg order value');
            $table->json('device_info')->nullable()->comment('IP, user agent, device fingerprint, geo location');
            $table->json('duplicate_ticket_info')->nullable()->comment('Matching ticket IDs, QR codes, event IDs');
            
            // Checkin-specific fields (from the 071002 schema)
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('first_check_in_at')->nullable();
            $table->uuid('first_check_in_by')->nullable();
            $table->timestamp('second_check_in_at')->nullable();
            $table->uuid('second_check_in_by')->nullable();
            
            // Review workflow
            $table->enum('status', [
                'flagged',
                'reviewed',
                'approved',
                'rejected',
                'auto_blocked'
            ])->default('flagged');
            $table->uuid('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            // General notes (from 071002)
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Soft deletes - fraud records should never be truly deleted,
            // but soft deletes allow safe "hiding" with recovery option
            $table->softDeletes();

            // === Foreign key constraints ===
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
            $table->foreign('first_check_in_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('second_check_in_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');

            // === Composite indexes for dashboard queries ===
            // Dashboard queries by user + time
            $table->index(['user_id', 'created_at'], 'idx_fraud_user_created');
            // Dashboard queries filtering by status + time
            $table->index(['status', 'created_at'], 'idx_fraud_status_created');
            // Dashboard queries filtering by risk level + time
            $table->index(['risk_level', 'created_at'], 'idx_fraud_risk_created');
            // Dashboard queries on order_id
            $table->index('order_id');
            // Dashboard queries for checkin fraud by ticket + event
            $table->index(['ticket_id', 'event_id'], 'idx_fraud_ticket_event');
            // Dashboard queries for checkin fraud by event + time
            $table->index(['event_id', 'detected_at'], 'idx_fraud_event_detected');
            // Quick filter by detection method
            $table->index('detection_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_events');
    }
};
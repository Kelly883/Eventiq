<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update refund_requests table
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->uuid('id')->primary()->change();
            $table->uuid('order_id')->nullable()->after('ticket_id');
            $table->uuid('event_id')->nullable()->after('order_id');
            $table->decimal('original_amount', 10, 2)->after('approved_amount');
            $table->decimal('refund_amount', 10, 2)->after('original_amount');
            $table->decimal('refund_percentage', 5, 2)->after('refund_amount');
            $table->string('reason')->after('refund_percentage');
            $table->text('explanation')->nullable()->after('reason');
            $table->string('refund_method')->after('explanation');
            $table->string('rejection_reason')->nullable()->after('status');
            $table->uuid('approved_by')->nullable()->after('rejection_reason');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('processing_started_at')->nullable()->after('approved_at');
            $table->timestamp('completed_at')->nullable()->after('processing_started_at');
            $table->string('payment_gateway_refund_id')->nullable()->after('completed_at');
            $table->json('payment_gateway_response')->nullable()->after('payment_gateway_refund_id');
            $table->integer('appeal_count')->default(0)->after('payment_gateway_response');
            $table->timestamp('last_appeal_at')->nullable()->after('appeal_count');

            $table->index(['user_id', 'status'], 'idx_refund_user_status');
            $table->index(['event_id', 'status'], 'idx_refund_event_status');
            $table->index('ticket_id');
        });

        // Update refund_policies table
        Schema::table('refund_policies', function (Blueprint $table) {
            $table->uuid('id')->primary()->change();
            $table->uuid('organizer_id')->nullable()->after('event_id');
            $table->decimal('refund_percentage_before_event', 5, 2)->after('refund_window_days');
            $table->decimal('refund_percentage_after_event_start', 5, 2)->after('refund_percentage_before_event');
            $table->boolean('allow_refunds_after_event_start')->default(false)->after('refund_percentage_after_event_start');
            $table->integer('processing_time_business_days')->default(3)->after('allow_refunds_after_event_start');
            $table->json('allowed_refund_methods')->nullable()->after('processing_time_business_days');
            $table->boolean('requires_approval')->default(false)->after('allowed_refund_methods');
            $table->decimal('auto_approve_threshold', 10, 2)->nullable()->after('requires_approval');
            $table->integer('max_refunds_per_user')->nullable()->after('auto_approve_threshold');
            $table->json('refund_reasons')->nullable()->after('max_refunds_per_user');
            $table->text('cancellation_policy')->nullable()->after('refund_reasons');
        });

        // Update refund_appeals table
        Schema::table('refund_appeals', function (Blueprint $table) {
            $table->uuid('id')->primary()->change();
            $table->text('review_notes')->nullable()->after('status');
            $table->uuid('reviewed_by')->nullable()->after('review_notes');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropIndex('idx_refund_user_status');
            $table->dropIndex('idx_refund_event_status');
            $table->dropColumn([
                'order_id', 'event_id', 'original_amount', 'refund_amount', 'refund_percentage',
                'reason', 'explanation', 'refund_method', 'rejection_reason', 'approved_by',
                'approved_at', 'processing_started_at', 'completed_at', 'payment_gateway_refund_id',
                'payment_gateway_response', 'appeal_count', 'last_appeal_at'
            ]);
            $table->id()->change();
        });

        Schema::table('refund_policies', function (Blueprint $table) {
            $table->dropColumn([
                'organizer_id', 'refund_percentage_before_event', 'refund_percentage_after_event_start',
                'allow_refunds_after_event_start', 'processing_time_business_days', 'allowed_refund_methods',
                'requires_approval', 'auto_approve_threshold', 'max_refunds_per_user', 'refund_reasons', 'cancellation_policy'
            ]);
            $table->id()->change();
        });

        Schema::table('refund_appeals', function (Blueprint $table) {
            $table->dropColumn(['review_notes', 'reviewed_by', 'reviewed_at']);
            $table->id()->change();
        });
    }
};
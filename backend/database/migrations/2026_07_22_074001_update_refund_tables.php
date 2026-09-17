<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        // Update refund_requests table
        $hasRefundRequestsOrderId = Schema::hasColumn('refund_requests', 'order_id');
        $hasRefundRequestsEventId = Schema::hasColumn('refund_requests', 'event_id');
        $hasRefundRequestsOriginalAmount = Schema::hasColumn('refund_requests', 'original_amount');
        $hasRefundRequestsRefundAmount = Schema::hasColumn('refund_requests', 'refund_amount');
        $hasRefundRequestsRefundPercentage = Schema::hasColumn('refund_requests', 'refund_percentage');
        $hasRefundRequestsExplanation = Schema::hasColumn('refund_requests', 'explanation');
        $hasRefundRequestsRefundMethod = Schema::hasColumn('refund_requests', 'refund_method');
        $hasRefundRequestsRejectionReason = Schema::hasColumn('refund_requests', 'rejection_reason');
        $hasRefundRequestsApprovedBy = Schema::hasColumn('refund_requests', 'approved_by');
        $hasRefundRequestsApprovedAt = Schema::hasColumn('refund_requests', 'approved_at');
        $hasRefundRequestsProcessingStartedAt = Schema::hasColumn('refund_requests', 'processing_started_at');
        $hasRefundRequestsCompletedAt = Schema::hasColumn('refund_requests', 'completed_at');
        $hasRefundRequestsPaymentGatewayRefundId = Schema::hasColumn('refund_requests', 'payment_gateway_refund_id');
        $hasRefundRequestsPaymentGatewayResponse = Schema::hasColumn('refund_requests', 'payment_gateway_response');
        $hasRefundRequestsAppealCount = Schema::hasColumn('refund_requests', 'appeal_count');
        $hasRefundRequestsLastAppealAt = Schema::hasColumn('refund_requests', 'last_appeal_at');
        Schema::table('refund_requests', function (Blueprint $table) use ($hasRefundRequestsOrderId, $hasRefundRequestsEventId, $hasRefundRequestsOriginalAmount, $hasRefundRequestsRefundAmount, $hasRefundRequestsRefundPercentage, $hasRefundRequestsExplanation, $hasRefundRequestsRefundMethod, $hasRefundRequestsRejectionReason, $hasRefundRequestsApprovedBy, $hasRefundRequestsApprovedAt, $hasRefundRequestsProcessingStartedAt, $hasRefundRequestsCompletedAt, $hasRefundRequestsPaymentGatewayRefundId, $hasRefundRequestsPaymentGatewayResponse, $hasRefundRequestsAppealCount, $hasRefundRequestsLastAppealAt) {
            if (!$hasRefundRequestsOrderId) {
                $table->uuid('order_id')->nullable()->after('ticket_id');
            }
            if (!$hasRefundRequestsEventId) {
                $table->foreignId('event_id')->nullable()->after('order_id');
            }
            if (!$hasRefundRequestsOriginalAmount) {
                $table->decimal('original_amount', 10, 2)->after('approved_amount');
            }
            if (!$hasRefundRequestsRefundAmount) {
                $table->decimal('refund_amount', 10, 2)->after('original_amount');
            }
            if (!$hasRefundRequestsRefundPercentage) {
                $table->decimal('refund_percentage', 5, 2)->after('refund_amount');
            }
            if (!$hasRefundRequestsExplanation) {
                $table->text('explanation')->nullable()->after('reason');
            }
            if (!$hasRefundRequestsRefundMethod) {
                $table->string('refund_method')->default('original')->after('explanation');
            }
            if (!$hasRefundRequestsRejectionReason) {
                $table->string('rejection_reason')->nullable()->after('status');
            }
            if (!$hasRefundRequestsApprovedBy) {
                $table->uuid('approved_by')->nullable()->after('rejection_reason');
            }
            if (!$hasRefundRequestsApprovedAt) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!$hasRefundRequestsProcessingStartedAt) {
                $table->timestamp('processing_started_at')->nullable()->after('approved_at');
            }
            if (!$hasRefundRequestsCompletedAt) {
                $table->timestamp('completed_at')->nullable()->after('processing_started_at');
            }
            if (!$hasRefundRequestsPaymentGatewayRefundId) {
                $table->string('payment_gateway_refund_id')->nullable()->after('completed_at');
            }
            if (!$hasRefundRequestsPaymentGatewayResponse) {
                $table->json('payment_gateway_response')->nullable()->after('payment_gateway_refund_id');
            }
            if (!$hasRefundRequestsAppealCount) {
                $table->integer('appeal_count')->default(0)->after('payment_gateway_response');
            }
            if (!$hasRefundRequestsLastAppealAt) {
                $table->timestamp('last_appeal_at')->nullable()->after('appeal_count');
            }
        });

        if ($this->indexExists('refund_requests', 'idx_refund_user_status')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_refund_user_status');
            });
        }

        if (!$this->indexExists('refund_requests', 'idx_refund_event_status')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index(['event_id', 'status'], 'idx_refund_event_status');
            });
        }

        if (!$this->indexExists('refund_requests', 'refund_requests_ticket_id_index')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index('ticket_id');
            });
        }

        // Update refund_policies table
        $hasRefundPoliciesOrganizerId = Schema::hasColumn('refund_policies', 'organizer_id');
        $hasRefundPoliciesRefundPercentageBeforeEvent = Schema::hasColumn('refund_policies', 'refund_percentage_before_event');
        $hasRefundPoliciesRefundPercentageAfterEventStart = Schema::hasColumn('refund_policies', 'refund_percentage_after_event_start');
        $hasRefundPoliciesAllowRefundsAfterEventStart = Schema::hasColumn('refund_policies', 'allow_refunds_after_event_start');
        $hasRefundPoliciesProcessingTimeBusinessDays = Schema::hasColumn('refund_policies', 'processing_time_business_days');
        $hasRefundPoliciesAllowedRefundMethods = Schema::hasColumn('refund_policies', 'allowed_refund_methods');
        $hasRefundPoliciesRequiresApproval = Schema::hasColumn('refund_policies', 'requires_approval');
        $hasRefundPoliciesAutoApproveThreshold = Schema::hasColumn('refund_policies', 'auto_approve_threshold');
        $hasRefundPoliciesMaxRefundsPerUser = Schema::hasColumn('refund_policies', 'max_refunds_per_user');
        $hasRefundPoliciesRefundReasons = Schema::hasColumn('refund_policies', 'refund_reasons');
        $hasRefundPoliciesCancellationPolicy = Schema::hasColumn('refund_policies', 'cancellation_policy');
        Schema::table('refund_policies', function (Blueprint $table) use ($hasRefundPoliciesOrganizerId, $hasRefundPoliciesRefundPercentageBeforeEvent, $hasRefundPoliciesRefundPercentageAfterEventStart, $hasRefundPoliciesAllowRefundsAfterEventStart, $hasRefundPoliciesProcessingTimeBusinessDays, $hasRefundPoliciesAllowedRefundMethods, $hasRefundPoliciesRequiresApproval, $hasRefundPoliciesAutoApproveThreshold, $hasRefundPoliciesMaxRefundsPerUser, $hasRefundPoliciesRefundReasons, $hasRefundPoliciesCancellationPolicy) {
            if (!$hasRefundPoliciesOrganizerId) {
                $table->foreignId('organizer_id')->nullable()->after('event_id');
            }
            if (!$hasRefundPoliciesRefundPercentageBeforeEvent) {
                $table->decimal('refund_percentage_before_event', 5, 2)->default(100)->after('refund_window_days');
            }
            if (!$hasRefundPoliciesRefundPercentageAfterEventStart) {
                $table->decimal('refund_percentage_after_event_start', 5, 2)->default(0)->after('refund_percentage_before_event');
            }
            if (!$hasRefundPoliciesAllowRefundsAfterEventStart) {
                $table->boolean('allow_refunds_after_event_start')->default(false)->after('refund_percentage_after_event_start');
            }
            if (!$hasRefundPoliciesProcessingTimeBusinessDays) {
                $table->integer('processing_time_business_days')->default(3)->after('allow_refunds_after_event_start');
            }
            if (!$hasRefundPoliciesAllowedRefundMethods) {
                $table->json('allowed_refund_methods')->nullable()->after('processing_time_business_days');
            }
            if (!$hasRefundPoliciesRequiresApproval) {
                $table->boolean('requires_approval')->default(false)->after('allowed_refund_methods');
            }
            if (!$hasRefundPoliciesAutoApproveThreshold) {
                $table->decimal('auto_approve_threshold', 10, 2)->nullable()->after('requires_approval');
            }
            if (!$hasRefundPoliciesMaxRefundsPerUser) {
                $table->integer('max_refunds_per_user')->nullable()->after('auto_approve_threshold');
            }
            if (!$hasRefundPoliciesRefundReasons) {
                $table->json('refund_reasons')->nullable()->after('max_refunds_per_user');
            }
            if (!$hasRefundPoliciesCancellationPolicy) {
                $table->text('cancellation_policy')->nullable()->after('refund_reasons');
            }
        });

        // Update refund_appeals table
        $hasRefundAppealsReviewNotes = Schema::hasColumn('refund_appeals', 'review_notes');
        $hasRefundAppealsReviewedBy = Schema::hasColumn('refund_appeals', 'reviewed_by');
        $hasRefundAppealsReviewedAt = Schema::hasColumn('refund_appeals', 'reviewed_at');
        Schema::table('refund_appeals', function (Blueprint $table) use ($hasRefundAppealsReviewNotes, $hasRefundAppealsReviewedBy, $hasRefundAppealsReviewedAt) {
            if (!$hasRefundAppealsReviewNotes) {
                $table->text('review_notes')->nullable()->after('status');
            }
            if (!$hasRefundAppealsReviewedBy) {
                $table->uuid('reviewed_by')->nullable()->after('review_notes');
            }
            if (!$hasRefundAppealsReviewedAt) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });
    }

    public function down(): void
    {
        $hasRefundRequestsExplanation = Schema::hasColumn('refund_requests', 'explanation');
        $hasRefundRequestsRefundMethod = Schema::hasColumn('refund_requests', 'refund_method');
        $hasRefundRequestsRejectionReason = Schema::hasColumn('refund_requests', 'rejection_reason');
        $hasRefundRequestsApprovedBy = Schema::hasColumn('refund_requests', 'approved_by');
        $hasRefundRequestsApprovedAt = Schema::hasColumn('refund_requests', 'approved_at');
        $hasRefundRequestsProcessingStartedAt = Schema::hasColumn('refund_requests', 'processing_started_at');
        $hasRefundRequestsCompletedAt = Schema::hasColumn('refund_requests', 'completed_at');
        $hasRefundRequestsAppealCount = Schema::hasColumn('refund_requests', 'appeal_count');
        $hasRefundRequestsLastAppealAt = Schema::hasColumn('refund_requests', 'last_appeal_at');
        Schema::table('refund_requests', function (Blueprint $table) use ($hasRefundRequestsExplanation, $hasRefundRequestsRefundMethod, $hasRefundRequestsRejectionReason, $hasRefundRequestsApprovedBy, $hasRefundRequestsApprovedAt, $hasRefundRequestsProcessingStartedAt, $hasRefundRequestsCompletedAt, $hasRefundRequestsAppealCount, $hasRefundRequestsLastAppealAt) {
            if ($hasRefundRequestsExplanation) {
                $table->dropColumn('explanation');
            }
            if ($hasRefundRequestsRefundMethod) {
                $table->dropColumn('refund_method');
            }
            if ($hasRefundRequestsRejectionReason) {
                $table->dropColumn('rejection_reason');
            }
            if ($hasRefundRequestsApprovedBy) {
                $table->dropColumn('approved_by');
            }
            if ($hasRefundRequestsApprovedAt) {
                $table->dropColumn('approved_at');
            }
            if ($hasRefundRequestsProcessingStartedAt) {
                $table->dropColumn('processing_started_at');
            }
            if ($hasRefundRequestsCompletedAt) {
                $table->dropColumn('completed_at');
            }
            if ($hasRefundRequestsAppealCount) {
                $table->dropColumn('appeal_count');
            }
            if ($hasRefundRequestsLastAppealAt) {
                $table->dropColumn('last_appeal_at');
            }
        });

        if ($this->indexExists('refund_requests', 'idx_refund_user_status')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropIndex('idx_refund_user_status');
            });
        }

        if ($this->indexExists('refund_requests', 'idx_refund_event_status')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropIndex('idx_refund_event_status');
            });
        }

        if ($this->indexExists('refund_requests', 'refund_requests_ticket_id_index')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropIndex('refund_requests_ticket_id_index');
            });
        }

        $hasRefundPoliciesOrganizerId = Schema::hasColumn('refund_policies', 'organizer_id');
        $hasRefundPoliciesRefundPercentageBeforeEvent = Schema::hasColumn('refund_policies', 'refund_percentage_before_event');
        $hasRefundPoliciesRefundPercentageAfterEventStart = Schema::hasColumn('refund_policies', 'refund_percentage_after_event_start');
        $hasRefundPoliciesAllowRefundsAfterEventStart = Schema::hasColumn('refund_policies', 'allow_refunds_after_event_start');
        $hasRefundPoliciesProcessingTimeBusinessDays = Schema::hasColumn('refund_policies', 'processing_time_business_days');
        $hasRefundPoliciesAllowedRefundMethods = Schema::hasColumn('refund_policies', 'allowed_refund_methods');
        $hasRefundPoliciesRequiresApproval = Schema::hasColumn('refund_policies', 'requires_approval');
        $hasRefundPoliciesAutoApproveThreshold = Schema::hasColumn('refund_policies', 'auto_approve_threshold');
        $hasRefundPoliciesMaxRefundsPerUser = Schema::hasColumn('refund_policies', 'max_refunds_per_user');
        $hasRefundPoliciesRefundReasons = Schema::hasColumn('refund_policies', 'refund_reasons');
        $hasRefundPoliciesCancellationPolicy = Schema::hasColumn('refund_policies', 'cancellation_policy');
        Schema::table('refund_policies', function (Blueprint $table) use ($hasRefundPoliciesOrganizerId, $hasRefundPoliciesRefundPercentageBeforeEvent, $hasRefundPoliciesRefundPercentageAfterEventStart, $hasRefundPoliciesAllowRefundsAfterEventStart, $hasRefundPoliciesProcessingTimeBusinessDays, $hasRefundPoliciesAllowedRefundMethods, $hasRefundPoliciesRequiresApproval, $hasRefundPoliciesAutoApproveThreshold, $hasRefundPoliciesMaxRefundsPerUser, $hasRefundPoliciesRefundReasons, $hasRefundPoliciesCancellationPolicy) {
            if ($hasRefundPoliciesOrganizerId) {
                $table->dropColumn('organizer_id');
            }
            if ($hasRefundPoliciesRefundPercentageBeforeEvent) {
                $table->dropColumn('refund_percentage_before_event');
            }
            if ($hasRefundPoliciesRefundPercentageAfterEventStart) {
                $table->dropColumn('refund_percentage_after_event_start');
            }
            if ($hasRefundPoliciesAllowRefundsAfterEventStart) {
                $table->dropColumn('allow_refunds_after_event_start');
            }
            if ($hasRefundPoliciesProcessingTimeBusinessDays) {
                $table->dropColumn('processing_time_business_days');
            }
            if ($hasRefundPoliciesAllowedRefundMethods) {
                $table->dropColumn('allowed_refund_methods');
            }
            if ($hasRefundPoliciesRequiresApproval) {
                $table->dropColumn('requires_approval');
            }
            if ($hasRefundPoliciesAutoApproveThreshold) {
                $table->dropColumn('auto_approve_threshold');
            }
            if ($hasRefundPoliciesMaxRefundsPerUser) {
                $table->dropColumn('max_refunds_per_user');
            }
            if ($hasRefundPoliciesRefundReasons) {
                $table->dropColumn('refund_reasons');
            }
            if ($hasRefundPoliciesCancellationPolicy) {
                $table->dropColumn('cancellation_policy');
            }
        });

        $hasRefundAppealsReviewNotes = Schema::hasColumn('refund_appeals', 'review_notes');
        $hasRefundAppealsReviewedBy = Schema::hasColumn('refund_appeals', 'reviewed_by');
        Schema::table('refund_appeals', function (Blueprint $table) use ($hasRefundAppealsReviewNotes, $hasRefundAppealsReviewedBy) {
            if ($hasRefundAppealsReviewNotes) {
                $table->dropColumn('review_notes');
            }
            if ($hasRefundAppealsReviewedBy) {
                $table->dropColumn('reviewed_by');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $row = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $indexName]
            );

            return $row !== null;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT i.relname FROM pg_index x '
                . 'JOIN pg_class i ON x.indexrelid = i.oid '
                . 'JOIN pg_class t ON x.indrelid = t.oid '
                . 'JOIN pg_namespace n ON t.relnamespace = n.oid '
                . 'WHERE n.nspname = current_schema() '
                . 'AND t.relname = ? '
                . 'AND i.relname = ?',
                [$table, $indexName]
            );

            return $row !== null;
        }

        if (DB::getDriverName() === 'mysql') {
            $row = DB::selectOne(
                'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
                [$table, $indexName]
            );

            return $row !== null;
        }

        return false;
    }
};
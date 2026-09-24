<?php

namespace App\Features\Refunds\Services;

use App\Features\Checkout\Models\Ticket;
use App\Features\Compliance\Services\AuditLogService;
use App\Features\Refunds\Models\RefundPolicy;
use App\Features\Refunds\Models\RefundRequest;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RefundService
{
    public function __construct(
        private PaymentGatewayService $paymentGatewayService,
        private AuditLogService $auditLogService
    ) {
    }

    public function requestRefund(string $userId, string $ticketId, string $reason, string $refundMethod = 'original_payment', ?string $explanation = null): RefundRequest
    {
        $ticket = Ticket::with(['order', 'ticketTier', 'event'])->findOrFail($ticketId);

        // Ownership check
        if ($ticket->user_id !== $userId) {
            throw new \RuntimeException('This ticket does not belong to you.', 403);
        }

        // Ticket status check
        if (!in_array($ticket->status, ['valid'])) {
            throw new \RuntimeException('This ticket is not eligible for refund. Current status: ' . $ticket->status, 403);
        }

        // Refund window check
        $event = $ticket->event;
        $policy = RefundPolicy::where('event_id', $event->id)->where('is_active', true)->first();

        if ($event->start_datetime->isPast() && (!$policy || !$policy->allow_refunds_after_event_start)) {
            throw new \RuntimeException('The event has already started and refunds are not allowed after the event begins.', 403);
        }

        // Duplicate check — use lockForUpdate for race condition protection
        return DB::transaction(function () use ($userId, $ticketId, $reason, $refundMethod, $explanation, $ticket, $event, $policy) {
            $existing = RefundRequest::where('ticket_id', $ticketId)
                ->whereIn('status', ['pending', 'approved', 'processing'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new \RuntimeException('A refund request for this ticket is already in progress.', 409);
            }

            // Calculate refund amount based on policy
            $originalAmount = $ticket->ticketTier->price ?? $ticket->order->total_amount ?? 0;
            $refundPercentage = $policy ? $policy->refund_percentage_before_event : 100.00;

            // If event started, check if different percentage applies
            if ($event->start_datetime->isPast() && $policy && $policy->refund_percentage_after_event_start) {
                $refundPercentage = $policy->refund_percentage_after_event_start;
            }

            $refundAmount = round($originalAmount * ($refundPercentage / 100), 2);

            // Determine if auto-approve
            $requiresApproval = $policy ? $policy->requires_approval : true;
            $autoApproveThreshold = $policy ? $policy->auto_approve_threshold : null;
            $status = 'pending';

            if (!$requiresApproval || ($autoApproveThreshold && $refundAmount <= $autoApproveThreshold)) {
                $status = 'approved';
            }

            // Get expected processing days
            $expectedProcessingDays = $policy ? $policy->processing_time_business_days : 3;

            $refundRequest = RefundRequest::create([
                'ticket_id' => $ticketId,
                'order_id' => $ticket->order_id,
                'user_id' => $userId,
                'event_id' => $event->id,
                'original_amount' => $originalAmount,
                'refund_amount' => $refundAmount,
                'refund_percentage' => $refundPercentage,
                'reason' => $reason,
                'explanation' => $explanation,
                'refund_method' => $refundMethod,
                'status' => $status,
                'reference_number' => 'REF-' . strtoupper(Str::random(10)),
                'expected_processing_days' => $expectedProcessingDays,
            ]);

            $this->auditLogService->log('refund.requested', 'refund_request', $refundRequest->id, [
                'ticket_id' => $ticketId,
                'original_amount' => $originalAmount,
                'refund_amount' => $refundAmount,
                'refund_percentage' => $refundPercentage,
                'status' => $status,
                'refund_method' => $refundMethod,
            ], $userId);

            return $refundRequest;
        });
    }

    /**
     * Approve a pending refund request, then actually process it through
     * the payment gateway. Approval and gateway processing happen
     * together here rather than as two separate admin actions, since
     * there's no indication a "approved but not yet refunded" state is
     * meaningfully different for this app.
     */
    public function approve(int $refundRequestId, int $adminUserId, ?float $approvedAmount, ?string $adminNotes = null): RefundRequest
    {
        $refundRequest = RefundRequest::findOrFail($refundRequestId);

        $previousStatus = $refundRequest->status;

        $refundRequest->update([
            'status' => 'approved',
            'approved_amount' => $approvedAmount ?? $refundRequest->refund_amount,
            'admin_notes' => $adminNotes,
            'reviewed_at' => now(),
            'reviewed_by' => $adminUserId,
            'approved_at' => now(),
            'approved_by' => $adminUserId,
        ]);

        try {
            $this->paymentGatewayService->processRefund($refundRequest->id);
            $refundRequest->update(['status' => 'refunded', 'completed_at' => now()]);

            $this->auditLogService->log('refund.approved', 'refund_request', $refundRequest->id, [
                'previous_status' => $previousStatus,
                'status' => 'refunded',
                'approved_amount' => $refundRequest->approved_amount,
            ], $adminUserId);

            // Mark the ticket cancelled once the refund actually succeeds.
            $refundRequest->ticket()->update(['status' => 'refunded']);
        } catch (\Throwable $e) {
            Log::error("RefundService::approve - gateway refund failed for request {$refundRequestId}: " . $e->getMessage());
            $refundRequest->update(['status' => 'approved']);
            $this->auditLogService->log('refund.gateway_failed', 'refund_request', $refundRequest->id, [
                'error' => $e->getMessage(),
            ], $adminUserId); // stays approved, not refunded - needs manual retry
            throw $e;
        }

        return $refundRequest->fresh();
    }

    public function reject(int $refundRequestId, int $adminUserId, string $adminNotes): RefundRequest
    {
        $refundRequest = RefundRequest::findOrFail($refundRequestId);

        $previousStatus = $refundRequest->status;

        $refundRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $adminNotes,
            'admin_notes' => $adminNotes,
            'reviewed_at' => now(),
            'reviewed_by' => $adminUserId,
        ]);

        $this->auditLogService->log('refund.rejected', 'refund_request', $refundRequest->id, [
            'previous_status' => $previousStatus,
            'status' => 'rejected',
        ], $adminUserId);

        return $refundRequest;
    }
}

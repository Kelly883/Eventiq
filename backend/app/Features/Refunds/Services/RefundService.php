<?php

namespace App\Features\Refunds\Services;

use App\Features\Checkout\Models\Ticket;
use App\Features\Compliance\Services\AuditLogService;
use App\Features\Fraud\Models\FraudEvent;
use App\Features\Refunds\Enums\RefundMethodEnum;
use App\Features\Refunds\Enums\RefundReasonEnum;
use App\Features\Refunds\Jobs\ProcessRefundJob;
use App\Features\Refunds\Models\RefundPolicy;
use App\Features\Refunds\Models\RefundRequest;
use App\Mail\RefundStatusUpdated;
use App\Mail\RefundRequested;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class RefundService
{
    public function __construct(
        private PaymentGatewayService $paymentGatewayService,
        private AuditLogService $auditLogService
    ) {
    }

    /**
     * Request a refund for a ticket.
     *
     * Supports guest tickets (user_id can be null) by verifying order email.
     * Validates payment method for original_payment_method refund method.
     * Supports idempotency keys for safe retries.
     */
    public function requestRefund(
        string $userId,
        string $ticketId,
        string $reason,
        string $refundMethod = RefundMethodEnum::ORIGINAL_PAYMENT_METHOD->value,
        ?string $explanation = null,
        ?string $idempotencyKey = null,
        ?string $guestEmail = null
    ): RefundRequest
    {
        $ticket = Ticket::with(['order', 'ticketTier', 'event.organizer.user'])->findOrFail($ticketId);

        // Handle guest tickets (user_id can be null in tickets table)
        $isGuestTicket = $ticket->user_id === null;

        // Ownership check - handle guest tickets
        if (!$isGuestTicket && $ticket->user_id !== $userId) {
            throw new \RuntimeException('This ticket does not belong to you.', 403);
        }

        // For guest tickets, verify ownership via order email
        if ($isGuestTicket && $guestEmail) {
            $ticketEmail = $ticket->attendee_email ?? $ticket->order->billing_email ?? null;
            if ($ticketEmail && !strcasecmp($ticketEmail, $guestEmail)) {
                throw new \RuntimeException('Guest ticket email mismatch.', 403);
            }
        }

        // Ticket status check
        if (!in_array($ticket->status, ['valid'])) {
            throw new \RuntimeException('This ticket is not eligible for refund. Current status: ' . $ticket->status, 403);
        }

        // Fraud check - block refunds for fraud-flagged tickets or orders
        $fraudEvent = FraudEvent::where(function ($q) use ($ticketId, $ticket) {
            $q->where('ticket_id', $ticketId)
                ->orWhere('order_id', $ticket->order_id);
        })
            ->where('status', 'auto_blocked')
            ->where('created_at', '>', now()->subDays(30))
            ->first();

        if ($fraudEvent) {
            throw new \RuntimeException('This ticket is not eligible for refund due to a fraud investigation.', 403);
        }

        // Refund window check
        $event = $ticket->event;
        $policy = RefundPolicy::where('event_id', $event->id)->where('is_active', true)->first();

        if ($event->start_datetime->isPast() && (!$policy || !$policy->allow_refunds_after_event_start)) {
            throw new \RuntimeException('The event has already started and refunds are not allowed after the event begins.', 403);
        }

        // Duplicate check — use lockForUpdate for race condition protection
        return DB::transaction(function () use ($userId, $ticketId, $reason, $refundMethod, $explanation, $ticket, $event, $policy, $idempotencyKey, $isGuestTicket) {
            // Check for existing refund with same idempotency key (for safe retries)
            if ($idempotencyKey) {
                $existingIdempotent = RefundRequest::where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existingIdempotent) {
                    return $existingIdempotent;
                }
            }

            $existing = RefundRequest::where('ticket_id', $ticketId)
                ->whereIn('status', ['pending', 'approved', 'processing'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new \RuntimeException('A refund request for this ticket is already in progress.', 409);
            }

            // Validate refund_method against policy allowed methods
            if ($policy && !empty($policy->allowed_refund_methods)) {
                if (!in_array($refundMethod, $policy->allowed_refund_methods)) {
                    throw new \RuntimeException('Refund method "' . $refundMethod . '" is not allowed for this event. Allowed methods: ' . implode(', ', $policy->allowed_refund_methods), 422);
                }
            }

            // Payment method validation for original_payment_method refund method
            if ($refundMethod === RefundMethodEnum::ORIGINAL_PAYMENT_METHOD->value && $ticket->order) {
                $userModel = $isGuestTicket ? null : app('App\Models\User')->find($userId);
                if ($userModel && !$userModel->hasPaymentMethod()) {
                    throw new \RuntimeException('Cannot issue refund to original payment: no valid payment method on file. Choose ' . RefundMethodEnum::STORE_CREDIT->value . ' or ' . RefundMethodEnum::ALTERNATIVE_PAYMENT_METHOD->value . '.', 422);
                }
                if (!$ticket->order->payment_gateway || !$ticket->order->gateway_transaction_id) {
                    throw new \RuntimeException('Cannot process refund to original payment: no gateway transaction found.', 422);
                }
            }

            // Calculate refund amount based on policy
            $originalAmount = $ticket->ticketTier->price ?? $ticket->order->total_amount ?? 0;
            $refundPercentage = $policy ? $policy->refund_percentage_before_event : 100.00;

            // If event started, check if different percentage applies.
            // Only override if the policy has an explicit after-event percentage;
            // otherwise keep the before-event percentage as the safe default.
            if ($event->start_datetime->isPast() && $policy && $policy->refund_percentage_after_event_start !== null) {
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

            // Get policy version for audit trail (policy versioning)
            $policyVersion = $policy ? $policy->version ?? null : null;

            $refundRequest = RefundRequest::create([
                'ticket_id' => $ticketId,
                'order_id' => $ticket->order_id,
                'user_id' => $isGuestTicket ? null : $userId,
                'event_id' => $event->id,
                'original_amount' => $originalAmount,
                'refund_amount' => $refundAmount,
                'refund_percentage' => $refundPercentage,
                'requested_amount' => $refundAmount,
                'reason' => $reason,
                'explanation' => $explanation,
                'refund_method' => $refundMethod,
                'status' => $status,
                'reference_number' => 'REF-' . strtoupper(Str::random(10)),
                'expected_processing_days' => $expectedProcessingDays,
                'idempotency_key' => $idempotencyKey,
                'policy_version_id' => $policyVersion,
            ]);

            $this->auditLogService->log('refund.requested', 'refund_request', $refundRequest->id, [
                'ticket_id' => $ticketId,
                'original_amount' => $originalAmount,
                'refund_amount' => $refundAmount,
                'refund_percentage' => $refundPercentage,
                'status' => $status,
                'refund_method' => $refundMethod,
                'is_guest_ticket' => $isGuestTicket,
            ], $isGuestTicket ? null : $userId);

            // For auto-approved refunds, queue the gateway processing
            if ($status === 'approved') {
                ProcessRefundJob::dispatch($refundRequest->id);
            }

            // Send email notification to user
            $recipientEmail = $ticket->attendee_email ?? $ticket->user?->email;
            if ($recipientEmail) {
                try {
                    Mail::to($recipientEmail)
                        ->queue(new RefundRequested($refundRequest));
                } catch (\Throwable $e) {
                    Log::warning('Failed to queue refund requested email: ' . $e->getMessage());
                }
            }

            // Send notification to organizer
            try {
                $organizerEmail = $event->organizer?->email;
                if ($organizerEmail) {
                    Mail::to($organizerEmail)
                        ->queue(new \App\Mail\RefundRequestedOrganizer($refundRequest));
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to queue organizer refund notification: ' . $e->getMessage());
            }

            return $refundRequest;
        });
    }

    /**
     * Approve a pending refund request, then queue processing through the payment gateway.
     */
    public function approve(string $refundRequestId, int $adminUserId, ?float $approvedAmount, ?string $adminNotes = null): RefundRequest
    {
        $refundRequest = RefundRequest::findOrFail($refundRequestId);

        $previousStatus = $refundRequest->status;

        // Update to approved status
        $refundRequest->update([
            'status' => 'approved',
            'approved_amount' => $approvedAmount ?? $refundRequest->refund_amount,
            'admin_notes' => $adminNotes,
            'reviewed_at' => now(),
            'reviewed_by' => $adminUserId,
            'approved_at' => now(),
            'approved_by' => $adminUserId,
        ]);

        // Queue the gateway processing asynchronously
        ProcessRefundJob::dispatch($refundRequest->id);

        $this->auditLogService->log('refund.approved', 'refund_request', $refundRequest->id, [
            'previous_status' => $previousStatus,
            'status' => 'approved',
            'approved_amount' => $refundRequest->approved_amount,
            'processing_queued' => true,
        ], $adminUserId);

        return $refundRequest->fresh();
    }

    public function reject(string $refundRequestId, int $adminUserId, string $adminNotes): RefundRequest
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

        // Send refund rejected email
        $this->sendStatusEmail($refundRequest);

        return $refundRequest;
    }

    /**
     * Appeal a rejected refund request.
     */
    public function appeal(string $refundRequestId, string $userId, string $appealReason): RefundRequest
    {
        $refundRequest = RefundRequest::findOrFail($refundRequestId);

        if ($refundRequest->user_id !== $userId) {
            throw new \RuntimeException('This refund request does not belong to you.', 403);
        }

        if ($refundRequest->status !== 'rejected') {
            throw new \RuntimeException('Only rejected refund requests can be appealed.', 422);
        }

        if ($refundRequest->appeal_count >= 3) {
            throw new \RuntimeException('Maximum number of appeals (3) reached.', 422);
        }

        $refundRequest->update([
            'status' => 'pending',
            'appeal_count' => $refundRequest->appeal_count + 1,
            'last_appeal_at' => now(),
        ]);

        $this->auditLogService->log('refund.appealed', 'refund_request', $refundRequest->id, [
            'appeal_count' => $refundRequest->appeal_count,
            'appeal_reason' => $appealReason,
        ], $userId);

        return $refundRequest->fresh();
    }

    /**
     * Send status update email to the refund requester.
     */
    private function sendStatusEmail(RefundRequest $refundRequest): void
    {
        try {
            $ticket = $refundRequest->ticket;
            $email = $ticket?->attendee_email ?? $refundRequest->user?->email;

            if ($email) {
                Mail::to($email)->queue(new RefundStatusUpdated($refundRequest));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to queue refund status email: ' . $e->getMessage());
        }
    }
}

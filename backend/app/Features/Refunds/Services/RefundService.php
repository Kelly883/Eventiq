<?php

namespace App\Features\Refunds\Services;

use App\Features\Checkout\Models\Ticket;
use App\Features\Refunds\Models\RefundRequest;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Facades\Log;

class RefundService
{
    public function __construct(private PaymentGatewayService $paymentGatewayService)
    {
    }

    public function requestRefund(int $userId, int $ticketId, string $reason): RefundRequest
    {
        $ticket = Ticket::with('order', 'ticketTier')->findOrFail($ticketId);

        if ($ticket->user_id !== $userId) {
            throw new \RuntimeException('This ticket does not belong to you.');
        }

        $existing = RefundRequest::where('ticket_id', $ticketId)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            throw new \RuntimeException('A refund request for this ticket is already in progress.');
        }

        return RefundRequest::create([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'status' => 'pending',
            'requested_amount' => $ticket->ticketTier->price ?? $ticket->order->total_amount ?? 0,
            'reason' => $reason,
        ]);
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

        $refundRequest->update([
            'status' => 'approved',
            'approved_amount' => $approvedAmount ?? $refundRequest->requested_amount,
            'admin_notes' => $adminNotes,
            'reviewed_at' => now(),
            'reviewed_by' => $adminUserId,
        ]);

        try {
            $this->paymentGatewayService->processRefund($refundRequest->id);
            $refundRequest->update(['status' => 'refunded']);

            // Mark the ticket cancelled once the refund actually succeeds.
            $refundRequest->ticket()->update(['status' => 'refunded']);
        } catch (\Throwable $e) {
            Log::error("RefundService::approve - gateway refund failed for request {$refundRequestId}: " . $e->getMessage());
            $refundRequest->update(['status' => 'approved']); // stays approved, not refunded - needs manual retry
            throw $e;
        }

        return $refundRequest->fresh();
    }

    public function reject(int $refundRequestId, int $adminUserId, string $adminNotes): RefundRequest
    {
        $refundRequest = RefundRequest::findOrFail($refundRequestId);

        $refundRequest->update([
            'status' => 'rejected',
            'admin_notes' => $adminNotes,
            'reviewed_at' => now(),
            'reviewed_by' => $adminUserId,
        ]);

        return $refundRequest;
    }
}

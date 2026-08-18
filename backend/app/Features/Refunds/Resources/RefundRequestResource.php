<?php

namespace App\Features\Refunds\Resources;

use App\Features\Refunds\Models\RefundAppeal;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'ticketId' => $this->ticket_id,
            'orderId' => $this->order_id,
            'userId' => $this->user_id,
            'eventId' => $this->event_id,
            'originalAmount' => (float) $this->original_amount,
            'refundAmount' => (float) $this->refund_amount,
            'refundPercentage' => (float) $this->refund_percentage,
            'formattedPercentage' => number_format((float) $this->refund_percentage, 0) . '%',
            'reason' => $this->reason,
            'explanation' => $this->explanation,
            'refundMethod' => $this->refund_method,
            'status' => $this->status,
            'rejectionReason' => $this->rejection_reason,
            'approvedBy' => $this->approved_by,
            'approvedAt' => $this->approved_at?->toIso8601String(),
            'processingStartedAt' => $this->processing_started_at?->toIso8601String(),
            'completedAt' => $this->completed_at?->toIso8601String(),
            'paymentGatewayRefundId' => $this->payment_gateway_refund_id,
            'paymentGateway' => $this->payment_gateway,
            'paymentIntentId' => $this->payment_intent_id,
            'paymentGatewayResponse' => $this->payment_gateway_response,
            'appealCount' => (int) $this->appeal_count,
            'lastAppealAt' => $this->last_appeal_at?->toIso8601String(),
            'formattedAmount' => '$' . number_format((float) $this->original_amount, 2),
            'formattedRefundAmount' => '$' . number_format((float) $this->refund_amount, 2),
            'formattedPercentage' => number_format((float) $this->refund_percentage, 0) . '%',
            'remainingAppealAttempts' => max(0, 3 - (int) $this->appeal_count),
            'timeRemainingForAppeal' => $this->time_remaining_for_appeal,
            'statusBadgeColor' => $this->status_badge_color,
            'isEligibleForAppeal' => $this->status === 'rejected' && (int) $this->appeal_count < 3,
            'canBeCancelled' => in_array($this->status, ['pending', 'processing'], true),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ]),
            'ticket' => $this->whenLoaded('ticket', fn () => [
                'id' => $this->ticket->id,
                'tier' => $this->ticket->ticketTier->name ?? null,
            ]),
            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order->id,
                'orderNumber' => $this->order->payment_intent_id,
            ]),
            'refundPolicy' => $this->whenLoaded('refundPolicy', fn () => [
                'id' => $this->refundPolicy->id,
                'formattedWindow' => $this->refundPolicy->formatted_window,
            ]),
            'payment' => $this->whenLoaded('payment', fn () => [
                'id' => $this->payment->id,
                'gateway' => $this->payment->gateway,
                'amount' => (float) $this->payment->amount,
                'currency' => $this->payment->currency,
                'status' => $this->payment->status,
                'paymentIntentId' => $this->payment->payment_intent_id,
                'gatewayTransactionId' => $this->payment->gateway_transaction_id,
                'paidAt' => $this->payment->paid_at?->toIso8601String(),
            ]),
            'appeals' => RefundAppealResource::collection($this->whenLoaded('appeals')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}

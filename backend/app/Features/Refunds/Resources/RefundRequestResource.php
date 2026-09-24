<?php

namespace App\Features\Refunds\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'refundRequestId' => $this->id,
            'status' => $this->status,
            'originalAmount' => $this->original_amount,
            'refundAmount' => $this->refund_amount,
            'refundPercentage' => $this->refund_percentage,
            'refundMethod' => $this->refund_method,
            'reason' => $this->reason,
            'expectedProcessingDays' => $this->expected_processing_days,
            'referenceNumber' => $this->reference_number,
            'adminNotes' => $this->admin_notes,
            'rejectionReason' => $this->rejection_reason,
            'reviewedAt' => $this->reviewed_at,
            'approvedAt' => $this->approved_at,
            'completedAt' => $this->completed_at,
            'ticket' => $this->whenLoaded('ticket', fn () => [
                'id' => $this->ticket->id,
                'event' => $this->ticket->relationLoaded('event') ? [
                    'id' => $this->ticket->event->id,
                    'title' => $this->ticket->event->title,
                ] : null,
            ]),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}

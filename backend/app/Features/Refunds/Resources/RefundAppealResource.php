<?php

namespace App\Features\Refunds\Resources;

use App\Features\Refunds\Models\RefundAppeal;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundAppealResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'refundRequestId' => $this->refund_request_id,
            'userId' => $this->user_id,
            'appealReason' => $this->appeal_reason,
            'status' => $this->status,
            'reviewedBy' => $this->reviewed_by,
            'reviewNotes' => $this->review_notes,
            'reviewedAt' => $this->reviewed_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}

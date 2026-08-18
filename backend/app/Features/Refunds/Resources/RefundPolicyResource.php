<?php

namespace App\Features\Refunds\Resources;

use App\Features\Refunds\Models\RefundPolicy;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundPolicyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'eventId' => $this->event_id,
            'organizerId' => $this->organizer_id,
            'refundWindowDays' => (int) $this->refund_window_days,
            'refundPercentageBeforeEvent' => (float) $this->refund_percentage_before_event,
            'refundPercentageAfterEventStart' => (float) $this->refund_percentage_after_event_start,
            'allowRefundsAfterEventStart' => (bool) $this->allow_refunds_after_event_start,
            'processingTimeBusinessDays' => (int) $this->processing_time_business_days,
            'allowedRefundMethods' => $this->allowed_refund_methods ?? [],
            'allowedMethodsList' => $this->allowed_methods_list,
            'requiresApproval' => (bool) $this->requires_approval,
            'autoApproveThreshold' => $this->auto_approve_threshold !== null ? (float) $this->auto_approve_threshold : null,
            'maxRefundsPerUser' => $this->max_refunds_per_user !== null ? (int) $this->max_refunds_per_user : null,
            'refundReasons' => $this->refund_reasons ?? [],
            'cancellationPolicy' => $this->cancellation_policy,
            'isActive' => (bool) $this->is_active,
            'formattedWindow' => $this->formatted_window,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}

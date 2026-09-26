<?php

namespace App\Features\Payouts\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
        public function toArray($request)
    {
        return [
            'id' => $this->id,
            'organizerId' => $this->organizer_id,
            'settlementPeriodStartDate' => $this->settlement_period_start_date?->toISO8601String(),
            'settlementPeriodEndDate' => $this->settlement_period_end_date?->toISO8601String(),
            'grossRevenue' => (float) $this->gross_revenue,
            'refundsDeducted' => (float) $this->refunds_deducted,
            'netRevenue' => (float) $this->net_revenue,
            'platformCommissionPercentage' => (float) $this->platform_commission_percentage,
            'platformCommissionAmount' => (float) $this->platform_commission_amount,
            'processingFeePercentage' => (float) $this->processing_fee_percentage,
            'processingFeeAmount' => (float) $this->processing_fee_amount,
            'payoutAmount' => (float) $this->payout_amount,
            'currency' => $this->currency,
            'payoutMethod' => $this->payout_method,
            'payoutMethodDetails' => $this->payout_method_details,
            'status' => $this->status,
            'completedAt' => $this->completed_at?->toISO8601String(),
            'createdAt' => $this->created_at?->toISO8601String(),
            'updatedAt' => $this->updated_at?->toISO8601String(),
        ];
    }
}

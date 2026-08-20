<?php

namespace App\Features\Payouts\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayoutCalculationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'payout_id' => $this->payout_id,
            'event_id' => $this->event_id,
            'total_revenue' => (float) $this->total_revenue,
            'platform_fee' => (float) $this->platform_fee,
            'organizer_share' => (float) $this->organizer_share,
            'tax_amount' => (float) $this->tax_amount,
            'refund_amount' => (float) $this->refund_amount,
            'breakdown' => $this->breakdown,
            'net_payout' => $this->calculateNetPayout(),
            'platform_fee_percentage' => $this->getPlatformFeePercentage(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

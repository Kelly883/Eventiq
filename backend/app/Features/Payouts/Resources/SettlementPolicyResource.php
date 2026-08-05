<?php

namespace App\Features\Payouts\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SettlementPolicyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'platform_fee_percentage' => (float) $this->platform_fee_percentage,
            'payout_frequency' => $this->payout_frequency,
            'payout_frequency_label' => $this->getFrequencyLabel(),
            'minimum_payout_amount' => (float) $this->minimum_payout_amount,
            'payment_methods' => $this->payment_methods,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

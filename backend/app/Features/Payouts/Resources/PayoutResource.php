<?php

namespace App\Features\Payouts\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'organizer_id' => $this->organizer_id,
            'event_id' => $this->event_id,
            'settlement_policy_id' => $this->settlement_policy_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payout_method' => $this->payout_method,
            'transaction_id' => $this->transaction_id,
            'processed_at' => $this->processed_at?->toISOString(),
            'notes' => $this->notes,
            'processed_by' => $this->processed_by,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'calculation' => $this->whenLoaded('calculation', function () {
                return new PayoutCalculationResource($this->calculation);
            }),
            'event' => $this->whenLoaded('event', function () {
                return [
                    'id' => $this->event->id,
                    'title' => $this->event->title ?? null,
                ];
            }),
            'organizer' => $this->whenLoaded('organizer', function () {
                return [
                    'id' => $this->organizer->id,
                    'name' => $this->organizer->name ?? $this->organizer->user?->name ?? null,
                ];
            }),
            'settlement_policy' => $this->whenLoaded('settlementPolicy', function () {
                return new SettlementPolicyResource($this->settlementPolicy);
            }),
        ];
    }
}

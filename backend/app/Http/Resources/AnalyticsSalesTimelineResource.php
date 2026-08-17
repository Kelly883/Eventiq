<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsSalesTimelineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'ticket_tier_id' => $this->ticket_tier_id,
            'pricing_window_id' => $this->pricing_window_id,
            'sale_timestamp' => $this->sale_timestamp,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_amount' => (float) $this->total_amount,
            'source' => $this->source?->value,
            'created_at' => $this->created_at,
        ];

        if ($request->user()?->can('viewPII', $this->resource)) {
            $data['buyer_email'] = $this->buyer_email;
        }

        return $data;
    }
}

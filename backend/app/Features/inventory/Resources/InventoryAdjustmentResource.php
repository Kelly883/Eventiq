<?php

namespace App\Features\Inventory\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAdjustmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'ticket_tier_id' => $this->ticket_tier_id,
            'pricing_window_id' => $this->pricing_window_id,
            'organizer_id' => $this->organizer_id,
            'adjustment_type' => $this->adjustment_type,
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'quantity_delta' => $this->quantity_delta,
            'reason' => $this->reason,
            'created_at' => $this->created_at,
        ];
    }
}

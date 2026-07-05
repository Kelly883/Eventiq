<?php

namespace App\Features\Inventory\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketInventoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'ticket_tier_id' => $this->ticket_tier_id,
            'pricing_window_id' => $this->pricing_window_id,
            'total_quantity' => $this->total_quantity,
            'sold_quantity' => $this->sold_quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'remaining' => $this->remaining,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

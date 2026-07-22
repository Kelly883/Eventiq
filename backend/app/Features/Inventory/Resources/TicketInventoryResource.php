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
            'total_allocated' => $this->total_allocated,
            'total_sold' => $this->total_sold,
            'total_available' => $this->total_available,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_low_stock' => $this->is_low_stock,
            'last_updated_at' => $this->last_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

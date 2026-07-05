<?php

namespace App\Features\Inventory\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAdjustmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'ticket_inventory_id' => $this->ticket_inventory_id,
            'user_id' => $this->user_id,
            'adjustment_type' => $this->adjustment_type,
            'quantity_change' => $this->quantity_change,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}

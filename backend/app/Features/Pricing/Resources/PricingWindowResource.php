<?php

namespace App\Features\Pricing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingWindowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'ticket_category_id' => $this->ticket_category_id,
            'window_name' => $this->window_name,
            'start_date_time' => $this->start_date_time,
            'end_date_time' => $this->end_date_time,
            'price' => (float) $this->price,
            'quantity_limit' => $this->quantity_limit,
            'quantity_sold' => $this->quantity_sold,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'has_availability' => $this->quantity_limit === null || $this->quantity_sold < $this->quantity_limit,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}


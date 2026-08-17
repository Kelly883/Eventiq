<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsTierPerformanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'ticket_tier_id' => $this->ticket_tier_id,
            'total_sold' => $this->total_sold,
            'total_revenue' => (float) $this->total_revenue,
            'average_price' => (float) $this->average_price,
            'percentage_of_total_sales' => (float) $this->percentage_of_total_sales,
            'percentage_of_total_revenue' => (float) $this->percentage_of_total_revenue,
            'conversion_rate' => (float) $this->conversion_rate,
            'last_updated_at' => $this->last_updated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

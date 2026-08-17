<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsEventsMetricResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'organizer_id' => $this->organizer_id,
            'total_revenue' => (string) $this->total_revenue,
            'total_tickets_sold' => (int) $this->total_tickets_sold,
            'total_page_views' => (int) $this->total_page_views,
            'total_ticket_page_views' => (int) $this->total_ticket_page_views,
            'conversion_rate' => (string) $this->conversion_rate,
            'average_ticket_price' => (string) $this->average_ticket_price,
            'peak_sales_hour' => $this->peak_sales_hour,
            'top_ticket_tier_id' => $this->top_ticket_tier_id,
            'last_updated_at' => $this->last_updated_at?->toIso8601String(),
            'trend' => $this->trend,
            'revenue_trend' => $this->revenue_trend,
            'tickets_sold_trend' => $this->tickets_sold_trend,
            'conversion_rate_trend' => $this->conversion_rate_trend,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

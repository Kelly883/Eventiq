<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardOverviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'totalTickets' => $this->resource['total_tickets'] ?? 0,
            'upcomingEventsCount' => $this->resource['upcoming_events_count'] ?? 0,
            'pastEventsCount' => $this->resource['past_events_count'] ?? 0,
            'totalSpent' => $this->resource['total_spent'] ?? 0,
            'totalRevenue' => $this->resource['total_revenue'] ?? 0,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreferencesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'defaultTicketFilter' => $this->resource->default_ticket_filter,
            'defaultDateRange' => $this->resource->default_date_range,
            'showRecommendations' => $this->resource->show_recommendations,
            'showActivityFeed' => $this->resource->show_activity_feed,
            'autoRefreshEnabled' => $this->resource->auto_refresh_enabled,
        ];
    }
}

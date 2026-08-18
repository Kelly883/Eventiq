<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreferencesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'defaultEventFilter' => $this->resource->default_event_filter,
            'defaultDateRange' => $this->resource->default_date_range,
            'expandedEventId' => $this->resource->expanded_event_id,
            'showActivityFeed' => $this->resource->show_activity_feed,
            'autoRefreshEnabled' => $this->resource->auto_refresh_enabled,
        ];
    }
}

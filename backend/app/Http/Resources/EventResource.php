<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Use attributesToArray to avoid loading all $with relations if not needed; parent::toArray respects $with
        // but we have already optimized queries with without('analyticsEventsMetric') for list endpoints.
        $data = parent::toArray($request);

        // Map DB status 'published' -> API 'live' for EventBrowsePage/CategoryBrowsePage which expect 'live'
        // Keep raw status as well for admin checks that expect 'published'
        $rawStatus = $this->status;
        $data['status'] = match ($rawStatus) {
            'published' => 'live',
            'archived' => 'past',
            default => $rawStatus,
        };
        $data['raw_status'] = $rawStatus;
        // is_public / isPublic compat for frontend (EventCreatePage sends isPublic, spec expects is_public)
        $data['is_public'] = $this->is_public ?? true;
        $data['isPublic'] = $this->is_public ?? true;

        $data['ticket_tiers'] = TicketTierResource::collection($this->whenLoaded('ticketTiers'));
        $data['organizer'] = $this->whenLoaded('organizer', function () {
            return new OrganizerPrivateResource($this->organizer);
        });
        $data['analyticsMetrics'] = $this->whenLoaded('analyticsEventsMetric', function () {
            return new AnalyticsEventsMetricResource($this->analyticsEventsMetric);
        });

        return $data;
    }
}

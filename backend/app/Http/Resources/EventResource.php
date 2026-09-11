<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['status'] = match ($this->status) {
            'published' => 'live',
            'archived' => 'past',
            default => $this->status,
        };
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

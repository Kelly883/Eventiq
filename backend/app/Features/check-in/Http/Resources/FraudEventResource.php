<?php

namespace App\Features\CheckIn\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FraudEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'ticketId' => $this->resource->ticket_id,
            'eventId' => $this->resource->event_id,
            'fraudType' => $this->resource->fraud_type,
            'detectedAt' => $this->resource->detected_at,
            'firstCheckInAt' => $this->resource->first_check_in_at,
            'firstCheckInBy' => $this->resource->first_check_in_by,
            'secondCheckInAt' => $this->resource->second_check_in_at,
            'secondCheckInBy' => $this->resource->second_check_in_by,
            'riskLevel' => $this->resource->risk_level,
            'notes' => $this->resource->notes,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }
}

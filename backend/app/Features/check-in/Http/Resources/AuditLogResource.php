<?php

namespace App\Features\CheckIn\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'eventId' => $this->resource->event_id,
            'userId' => $this->resource->user_id,
            'action' => $this->resource->action,
            'ticketId' => $this->resource->ticket_id,
            'details' => $this->resource->details,
            'createdAt' => $this->resource->created_at,
        ];
    }
}

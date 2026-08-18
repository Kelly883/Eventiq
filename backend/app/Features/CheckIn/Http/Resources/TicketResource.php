<?php

namespace App\Features\CheckIn\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'eventId' => $this->resource->event_id,
            'orderId' => $this->resource->order_id,
            'ticketTierId' => $this->resource->ticket_tier_id,
            'ticketId' => $this->resource->ticket_id,
            'attendeeName' => $this->resource->attendee_name,
            'attendeeEmail' => $this->resource->attendee_email,
            'tier' => $this->resource->tier,
            'qrCodeData' => $this->resource->qr_code_data,
            'status' => $this->resource->status,
            'checkedInAt' => $this->resource->checked_in_at,
            'checkedInBy' => $this->resource->checked_in_by,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }
}

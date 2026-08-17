<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'eventId' => $this->resource->event_id,
            'eventTitle' => $this->resource->event->title ?? null,
            'eventDate' => $this->resource->event->start_datetime ?? null,
            'eventVenue' => $this->resource->event->venue_name ?? null,
            'tierId' => $this->resource->ticket_tier_id,
            'tierName' => $this->resource->ticketTier->name ?? null,
            'qrCodeData' => $this->resource->qr_code_data,
            'status' => $this->resource->status,
            'deliveryStatus' => $this->resource->delivery_status ?? null,
            'deliveryMethod' => $this->resource->delivery_method ?? null,
            'deliveryTimestamp' => $this->resource->delivery_timestamp ?? null,
            'orderId' => $this->resource->order_id,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }
}

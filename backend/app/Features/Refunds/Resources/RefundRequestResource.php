<?php

namespace App\Features\Refunds\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'requested_amount' => $this->requested_amount,
            'approved_amount' => $this->approved_amount,
            'reason' => $this->reason,
            'admin_notes' => $this->admin_notes,
            'reviewed_at' => $this->reviewed_at,
            'ticket' => $this->whenLoaded('ticket', fn () => [
                'id' => $this->ticket->id,
                'event' => $this->ticket->relationLoaded('event') ? [
                    'id' => $this->ticket->event->id,
                    'title' => $this->ticket->event->title,
                ] : null,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

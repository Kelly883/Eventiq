<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookDeliveryLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'webhookId' => $this->webhook_id,
            'event' => $this->event,
            'attemptNumber' => $this->attempt_number,
            'payload' => $this->payload ?: [],
            'status' => $this->status,
            'responseCode' => $this->response_code,
            'responseBody' => $this->response_body,
            'errorMessage' => $this->error_message,
            'durationMs' => $this->duration_ms,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}

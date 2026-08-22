<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organizerId' => $this->organizer_id,
            'url' => $this->url,
            'description' => $this->description,
            'subscribedEvents' => $this->subscribed_events ?: [],
            'status' => $this->status,
            'failureCount' => $this->failure_count,
            'lastSuccessAt' => $this->last_success_at?->toIso8601String(),
            'lastFailureAt' => $this->last_failure_at?->toIso8601String(),
            'timeoutSeconds' => $this->timeout_seconds,
            'retryPolicy' => $this->retry_policy ?: [],
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}

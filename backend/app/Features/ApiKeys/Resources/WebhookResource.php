<?php

namespace App\Features\ApiKeys\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WebhookResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'description' => $this->description,
            'subscribed_events' => $this->subscribed_events,
            'status' => $this->status,
            'failure_count' => $this->failure_count,
            'last_success_at' => $this->last_success_at,
            'last_failure_at' => $this->last_failure_at,
            'timeout_seconds' => $this->timeout_seconds,
            'retry_policy' => $this->retry_policy,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

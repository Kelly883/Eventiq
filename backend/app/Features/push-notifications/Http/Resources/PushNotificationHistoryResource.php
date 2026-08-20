<?php

namespace App\Features\PushNotifications\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PushNotificationHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'userId' => $this->resource->user_id,
            'deviceId' => $this->resource->device_id,
            'templateId' => $this->resource->template_id,
            'title' => $this->resource->title,
            'body' => $this->resource->body,
            'data' => $this->resource->data,
            'status' => $this->resource->status,
            'sentAt' => $this->resource->sent_at,
            'deliveredAt' => $this->resource->delivered_at,
            'openedAt' => $this->resource->opened_at,
            'errorMessage' => $this->resource->error_message,
            'gatewayResponse' => $this->resource->gateway_response,
            'createdAt' => $this->resource->created_at,
        ];
    }
}

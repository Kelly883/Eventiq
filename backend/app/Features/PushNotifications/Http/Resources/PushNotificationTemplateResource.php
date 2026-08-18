<?php

namespace App\Features\PushNotifications\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PushNotificationTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'type' => $this->resource->type,
            'title' => $this->resource->title,
            'body' => $this->resource->body,
            'variables' => $this->resource->variables ?? [],
            'isActive' => (bool) $this->resource->is_active,
            'priority' => $this->resource->priority,
            'badge' => $this->resource->badge,
            'sound' => $this->resource->sound,
            'clickAction' => $this->resource->click_action,
            'collapseKey' => $this->resource->collapse_key,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }
}

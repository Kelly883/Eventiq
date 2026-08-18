<?php

namespace App\Features\PushNotifications\Resources;

use App\Features\PushNotifications\Models\PushNotificationTemplate;
use Illuminate\Http\Resources\Json\JsonResource;

class PushNotificationTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'type' => $this->resource->type,
            'title' => $this->resource->title,
            'body' => $this->resource->body,
            'variables' => $this->resource->variables ?? [],
            'isActive' => (bool) $this->resource->is_active,
            'priority' => $this->resource->priority ?? 0,
            'badge' => $this->resource->badge ?? 0,
            'sound' => $this->resource->sound,
            'clickAction' => $this->resource->click_action,
            'collapseKey' => $this->resource->collapse_key,
            'createdAt' => $this->resource->created_at?->toIso8601String(),
            'updatedAt' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}

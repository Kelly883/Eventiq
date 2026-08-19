<?php

namespace App\Features\admin\Resources;

use App\Features\admin\Models\AuditLog as AuditLogModel;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray($request): array
    {
        $resource = $this->resource instanceof AuditLogModel
            ? $this->resource
            : AuditLogModel::from($this->resource->toArray());

        return [
            'id' => (string) $resource->id,
            'userId' => (string) $resource->user_id,
            'action' => $resource->action,
            'targetType' => $resource->target_type,
            'targetId' => (string) $resource->target_id,
            'description' => $resource->description,
            'status' => $resource->status,
            'metadata' => $resource->metadata ?: [],
            'createdAt' => $resource->created_at?->toIso8601String(),
            'performedByName' => $resource->user->name ?? null,
        ];
    }
}

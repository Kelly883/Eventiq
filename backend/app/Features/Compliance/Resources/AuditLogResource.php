<?php

namespace App\Features\Compliance\Resources;

use App\Features\Compliance\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray($request): array
    {
        $resource = $this->resource instanceof AuditLog
            ? $this->resource
            : new AuditLog($this->resource->toArray());

        $user = $resource->relationLoaded('user') ? $resource->user : null;
        $performedByName = $user instanceof User ? $user->name : null;

        return [
            'id' => (string) $resource->id,
            'userId' => (string) ($resource->user_id ?? ''),
            'performedByName' => $performedByName,
            'action' => $resource->action,
            'targetType' => $resource->target_type,
            'targetId' => $resource->target_id !== null ? (string) $resource->target_id : null,
            'targetName' => $resource->getTargetName(),
            'description' => $resource->description,
            'source' => $resource->source,
            'ipAddress' => $resource->ip_address,
            'userAgent' => $resource->user_agent,
            'geolocation' => $resource->geolocation,
            'requestData' => $resource->request_data,
            'responseData' => $resource->response_data,
            'changedFields' => $resource->changed_fields,
            'errorMessage' => $resource->error_message,
            'errorCode' => $resource->error_code,
            'status' => $resource->status,
            'complianceClassification' => $resource->compliance_classification,
            'retentionDate' => $resource->retention_date,
            'retentionReason' => $resource->retention_reason,
            'metadata' => $resource->metadata ?: [],
            'createdAt' => $resource->created_at?->toIso8601String(),
            'updatedAt' => $resource->updated_at?->toIso8601String(),
            'deletedAt' => $resource->deleted_at,
        ];
    }
}

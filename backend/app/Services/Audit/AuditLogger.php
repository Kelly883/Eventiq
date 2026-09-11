<?php

namespace App\Services\Audit;

use App\Features\Compliance\Enums\AuditLogAction;
use App\Features\Compliance\Models\AuditLog as ComplianceAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(
        string|AuditLogAction $action,
        ?User $user,
        string $resourceType = 'event',
        ?string $resourceId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null,
    ): ComplianceAuditLog {
        $actionEnum = $action instanceof AuditLogAction
            ? $action
            : AuditLogAction::tryFrom($action) ?? AuditLogAction::USER_LOGIN;
        $metadata = [];

        if ($request) {
            $metadata = array_merge($metadata, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'request_id' => $request->header('X-Request-ID') ?? uniqid(),
            ]);
        }

        $changedFields = [];
        if ($oldValues !== null && $newValues !== null) {
            $changedFields = array_keys(array_diff_assoc($newValues, $oldValues));
        }

        return ComplianceAuditLog::create([
            'user_id' => $user?->id,
            'action' => $actionEnum,
            'target_type' => $resourceType,
            'target_id' => $resourceId,
            'description' => $description,
            'changed_fields' => $changedFields,
            'request_data' => $newValues,
            'response_data' => $oldValues,
            'ip_address' => $metadata['ip'] ?? null,
            'user_agent' => $metadata['user_agent'] ?? null,
            'metadata' => $metadata,
            'compliance_classification' => 'internal',
            'status' => 'success',
        ]);
    }

    public static function forEvent(
        string|AuditLogAction $action,
        ?User $user,
        ?string $eventId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null,
        ?string $description = null
    ): ComplianceAuditLog {
        return self::log($action, $user, 'event', $eventId, $description, $oldValues, $newValues, $request);
    }
}

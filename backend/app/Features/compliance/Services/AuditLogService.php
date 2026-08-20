<?php

namespace App\Features\Compliance\Services;

use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditLogService
{
    /**
     * Records an audit event. Writes to both the audit_logs table (for
     * querying/filtering in the admin UI) and a dedicated file-based log
     * channel (config/logging.php's 'audit' channel) - the file copy
     * survives even if the database is temporarily unavailable, which is
     * the whole point of having a separate trail per the original
     * requirement.
     */
    public function log(string $action, string $targetType, $targetId, array $changes = [], $userId = null, ?string $requestId = null): ?AuditLog
    {
        if (! config('audit.enabled', true)) {
            return null;
        }

        $requestId ??= request()?->attributes->get('request_id')
            ?? request()?->headers->get('X-Request-Id')
            ?? (string) Str::uuid();

        $metadata = [
            'requestId' => $requestId,
            'sessionId' => request()?->attributes->get('session_id'),
            'correlationId' => request()?->attributes->get('correlation_id'),
            'duration_ms' => null,
            'dataSize_bytes' => null,
            'tags' => [],
        ];

        $ipAddress = request()?->ip();
        $userAgent = request()?->userAgent();
        $source = $this->detectSource();

        Log::channel('audit')->info($action, [
            'target_type' => $targetType,
            'target_id' => $targetId,
            'user_id' => $userId,
            'changes' => $changes,
            'request_id' => $requestId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        try {
            return AuditLog::create([
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'changed_fields' => $changes,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'source' => $source,
                'user_agent' => $userAgent,
                'status' => 'success',
                'compliance_classification' => 'internal',
                'retention_date' => now()->addYears(7),
                'metadata' => $metadata,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('audit_log_db_write_failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function detectSource(): string
    {
        if (app()->runningInConsole()) {
            return 'cli';
        }

        if (request()?->expectsJson() || request()?->is('api/*')) {
            return 'api';
        }

        return 'web';
    }

    /**
     * @param array $filters Optional keys: action, target_type, target_id,
     *   user_id, from (date), to (date), per_page
     */
    public function filter(array $filters): LengthAwarePaginator
    {
        $query = AuditLog::query()->with('user')->latest();

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['target_type'])) {
            $query->where('target_type', $filters['target_type']);
        }

        if (! empty($filters['target_id'])) {
            $query->where('target_id', $filters['target_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }
}

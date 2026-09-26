<?php

namespace App\Features\Compliance\Services;

use App\Features\Compliance\Models\AuditLog;
use App\Features\Compliance\Models\AuditLogTag;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditLogService
{
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
            Log::channel('audit')->error('audit_log_db_write_failed', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

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

    public function filter(array $filters): LengthAwarePaginator
    {
        $query = AuditLog::query()->with('user');

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

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['classification'])) {
            $query->where('compliance_classification', $filters['classification']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        $sortBy = $filters['sortBy'] ?? 'createdAt';
        $sortOrder = $filters['sortOrder'] ?? 'desc';

        $sortColumn = match ($sortBy) {
            'action' => 'action',
            'status' => 'status',
            default => 'created_at',
        };

        $query->orderBy($sortColumn, $sortOrder === 'desc' ? 'desc' : 'asc');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function find(string $logId): ?AuditLog
    {
        return AuditLog::find($logId);
    }

    public function bulkTag(array $logIds, string $tag): int
    {
        $now = now();
        $records = [];

        foreach ($logIds as $logId) {
            $records[] = [
                'id' => (string) Str::uuid(),
                'audit_log_id' => $logId,
                'tag' => $tag,
                'created_at' => $now,
            ];
        }

        if (empty($records)) {
            return 0;
        }

        AuditLogTag::insert($records);

        return count($records);
    }

    public function summary(array $filters = []): array
    {
        $query = AuditLog::query();

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['target_type'])) {
            $query->where('target_type', $filters['target_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['classification'])) {
            $query->where('compliance_classification', $filters['classification']);
        }

        $total = $query->count();
        $failedCount = (clone $query)->where('status', 'failure')->count();
        $successCount = (clone $query)->where('status', 'success')->count();

        $oldestRetention = AuditLog::whereNotNull('retention_date')->min('retention_date');
        $retentionDaysRemaining = $oldestRetention
            ? now()->diffInDays(Carbon::parse($oldestRetention), false)
            : 0;

        return [
            'totalEvents' => $total,
            'successRate' => $total > 0 ? round(($successCount / $total) * 100, 2) : 0.0,
            'failedCount' => $failedCount,
            'retentionDaysRemaining' => max(0, (int) $retentionDaysRemaining),
        ];
    }
}

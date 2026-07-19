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
    public function log(string $action, string $entity, ?int $entityId, array $changes = [], ?int $userId = null, ?string $requestId = null): ?AuditLog
    {
        if (! config('audit.enabled', true)) {
            return null;
        }

        $requestId ??= request()?->attributes->get('request_id')
            ?? request()?->headers->get('X-Request-Id')
            ?? (string) Str::uuid();

        // File write first: if the DB is genuinely down, this is the
        // trail that actually survives - writing DB-first would lose
        // the event entirely in that scenario.
        Log::channel('audit')->info($action, [
            'entity' => $entity,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'changes' => $changes,
            'request_id' => $requestId,
        ]);

        try {
            return AuditLog::create([
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'changes' => $changes,
                'user_id' => $userId,
                'request_id' => $requestId,
            ]);
        } catch (\Throwable $e) {
            // Deliberately swallowed: an audit-logging failure must never
            // break the actual business operation that triggered it (a
            // refund, a payout, etc.) - the file write above already
            // captured the event, which is exactly the scenario this
            // fallback exists for.
            Log::channel('audit')->error('audit_log_db_write_failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param array $filters Optional keys: action, entity, entity_id,
     *   user_id, from (date), to (date), per_page
     */
    public function filter(array $filters): LengthAwarePaginator
    {
        $query = AuditLog::query()->with('user')->latest();

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['entity'])) {
            $query->where('entity', $filters['entity']);
        }

        if (! empty($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
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

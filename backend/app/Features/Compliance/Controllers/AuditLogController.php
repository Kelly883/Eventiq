<?php

namespace App\Features\Compliance\Controllers;

use App\Features\Compliance\Requests\AuditLogIndexRequest;
use App\Features\Compliance\Resources\AuditLogResource;
use App\Features\Compliance\Services\AuditLogService;
use App\Features\Compliance\Services\ExportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService,
        private ExportService $exportService
    ) {
    }

    private function touchAdminLastUsedAt(Request $request): void
    {
        if ($request->user()?->admin_last_used_at !== null) {
            $request->user()->update(['admin_last_used_at' => now()]);
        }
    }

    private function mapFilters(array $filters): array
    {
        return [
            'action' => $filters['action'] ?? null,
            'target_type' => $filters['targetType'] ?? null,
            'target_id' => $filters['targetId'] ?? null,
            'user_id' => $filters['userId'] ?? null,
            'status' => $filters['status'] ?? null,
            'classification' => $filters['classification'] ?? null,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'sortBy' => $filters['sortBy'] ?? 'createdAt',
            'sortOrder' => $filters['sortOrder'] ?? 'desc',
            'per_page' => $filters['per_page'] ?? 20,
            'format' => $filters['format'] ?? 'json',
        ];
    }

    public function index(AuditLogIndexRequest $request)
    {
        $this->touchAdminLastUsedAt($request);

        $filters = $this->mapFilters($request->validated());
        $results = $this->auditLogService->filter($filters);
        $summary = $this->auditLogService->summary($filters);

        return response()->json([
            'data' => AuditLogResource::collection($results->items())->resolve(),
            'summary' => $summary,
            'meta' => [
                'total' => $results->total(),
                'page' => $results->currentPage(),
                'perPage' => $results->perPage(),
            ],
        ]);
    }

    public function show(Request $request, string $logId)
    {
        $this->touchAdminLastUsedAt($request);

        $log = $this->auditLogService->find($logId);

        if (!$log) {
            return response()->json(['message' => 'Audit log not found'], 404);
        }

        return response()->json([
            'data' => new AuditLogResource($log),
        ]);
    }

    public function summary(AuditLogIndexRequest $request)
    {
        $this->touchAdminLastUsedAt($request);

        $filters = $this->mapFilters($request->validated());
        $summary = $this->auditLogService->summary($filters);

        return response()->json($summary);
    }

    public function export(AuditLogIndexRequest $request)
    {
        $this->touchAdminLastUsedAt($request);

        $filters = $this->mapFilters($request->validated());
        $format = $filters['format'] ?? 'json';

        $query = \App\Features\Compliance\Models\AuditLog::query()->with('user');

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['target_type'])) {
            $query->where('target_type', $filters['target_type']);
        }

        if (!empty($filters['target_id'])) {
            $query->where('target_id', $filters['target_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['classification'])) {
            $query->where('compliance_classification', $filters['classification']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        $this->auditLogService->log('compliance.audit_logs.export', 'audit_log', null, [
            'format' => $format,
            'filters' => $filters,
        ], $request->user()?->id);

        $content = $this->exportService->export($query, $format);

        $filename = 'audit-logs-' . now()->format('Y-m-d-H-i-s') . ($format === 'csv' ? '.csv' : '.json');

        return response($content, 200, [
            'Content-Type' => $format === 'csv' ? 'text/csv; charset=utf-8' : 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function bulkTag(Request $request)
    {
        $this->touchAdminLastUsedAt($request);

        $request->validate([
            'logIds' => ['required', 'array'],
            'logIds.*' => ['uuid', 'exists:audit_logs,id'],
            'tag' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        if (!$user || !$user->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'logIds' => ['required', 'array'],
            'logIds.*' => ['uuid', 'exists:audit_logs,id'],
            'tag' => ['required', 'string', 'max:255'],
        ]);

        $updated = $this->auditLogService->bulkTag($validated['logIds'], $validated['tag']);

        $this->auditLogService->log('compliance.audit_logs.bulk_tag', 'audit_log', null, [
            'tag' => $validated['tag'],
            'logIds' => $validated['logIds'],
            'updated_count' => $updated,
        ], $user->id);

        return response()->json([
            'updated' => $updated,
        ]);
    }
}

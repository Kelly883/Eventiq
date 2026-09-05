<?php

namespace App\Features\Compliance\Controllers;

use App\Features\Compliance\Requests\AuditLogIndexRequest;
use App\Features\Compliance\Services\AuditLogService;
use App\Http\Controllers\Controller;

class AuditLogController extends Controller
{
    public function __construct(private AuditLogService $auditLogService)
    {
    }

    /**
     * GET /api/admin/compliance/audit-logs
     */
    public function index(AuditLogIndexRequest $request)
    {
        $results = $this->auditLogService->filter($request->validated());

        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'page' => $results->currentPage(),
                'perPage' => $results->perPage(),
            ],
        ]);
    }

    public function show(string $logId)
    {
        $log = $this->auditLogService->find($logId);

        return response()->json([
            'data' => $log,
        ]);
    }

    public function export(AuditLogIndexRequest $request)
    {
        $results = $this->auditLogService->filter($request->validated());

        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'page' => $results->currentPage(),
                'perPage' => $results->perPage(),
            ],
        ]);
    }

    public function bulkTag(Request $request)
    {
        $validated = $request->validate([
            'logIds' => ['required', 'array'],
            'logIds.*' => ['uuid', 'exists:audit_logs,id'],
            'tag' => ['required', 'string', 'max:255'],
        ]);

        $updated = $this->auditLogService->bulkTag($validated['logIds'], $validated['tag']);

        return response()->json([
            'updated' => $updated,
        ]);
    }
}

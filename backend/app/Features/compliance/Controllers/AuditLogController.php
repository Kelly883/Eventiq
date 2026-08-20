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
}

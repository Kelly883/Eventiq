<?php

namespace App\Features\Compliance\Controllers;

use App\Features\Compliance\Requests\AuditLogIndexRequest;
use App\Features\Compliance\Resources\AuditLogResource;
use App\Features\Compliance\Services\AuditLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(private AuditLogService $auditLogService)
    {
    }

    private function touchAdminLastUsedAt(Request $request): void
    {
        if ($request->user()?->admin_last_used_at !== null) {
            $request->user()->update(['admin_last_used_at' => now()]);
        }
    }

    /**
     * GET /api/admin/compliance/audit-logs
     */
     public function index(AuditLogIndexRequest $request)
     {
         $this->touchAdminLastUsedAt($request);

         $results = $this->auditLogService->filter($request->validated());

         return response()->json([
             'data' => AuditLogResource::collection($results->items())->resolve(),
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

     public function export(AuditLogIndexRequest $request)
     {
         $this->touchAdminLastUsedAt($request);

         $results = $this->auditLogService->filter($request->validated());

         return response()->json([
             'data' => AuditLogResource::collection($results->items())->resolve(),
             'meta' => [
                 'total' => $results->total(),
                 'page' => $results->currentPage(),
                 'perPage' => $results->perPage(),
             ],
         ]);
     }

    public function bulkTag(Request $request)
    {
        $this->touchAdminLastUsedAt($request);

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

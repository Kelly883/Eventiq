<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);

        $logs = AuditLog::where('user_id', $userId)
            ->where('action', 'api_call')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $logs->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'targetType' => $log->target_type,
                'targetId' => $log->target_id,
                'status' => $log->status,
                'source' => $log->source,
                'path' => $log->metadata['path'] ?? ($log->metadata['url'] ?? null),
                'ipAddress' => $log->ip_address,
                'createdAt' => $log->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'links' => [
                'first' => $logs->url(1),
                'last' => $logs->url($logs->lastPage()),
                'prev' => $logs->previousPageUrl(),
                'next' => $logs->nextPageUrl(),
            ],
        ], 200);
    }
}

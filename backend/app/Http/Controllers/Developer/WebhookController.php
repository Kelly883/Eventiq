<?php

namespace App\Http\Controllers\Developer;

use App\Features\ApiKeys\Requests\StoreWebhookRequest;
use App\Features\ApiKeys\Resources\WebhookResource;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $webhooks = Webhook::where('organizer_id', $userId)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => WebhookResource::collection($webhooks),
            'message' => 'Webhooks loaded',
        ], 200);
    }

    public function store(Request $request, StoreWebhookRequest $req): JsonResponse
    {
        $userId = $request->user()?->id;

        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $webhook = Webhook::create([
            'organizer_id' => $userId,
            'url' => $req->validated('url'),
            'description' => $req->validated('description'),
            'secret' => Webhook::generateSecret(),
            'subscribed_events' => $req->validated('subscribedEvents'),
            'status' => 'active',
            'failure_count' => 0,
            'timeout_seconds' => 10,
            'retry_policy' => ['max_attempts' => 3, 'backoff_seconds' => 60],
        ]);

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'webhook_created',
            'target_type' => Webhook::class,
            'target_id' => $webhook->id,
            'status' => 'success',
            'source' => 'developer_portal',
            'ip_address' => $request->ip(),
            'metadata' => ['url' => $webhook->url, 'events' => $webhook->subscribed_events],
        ]);

        return response()->json(
            array_merge($webhook->toArray(), ['message' => 'Webhook created']),
            201
        );
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()?->id;

        if (! $userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $webhook = Webhook::where('organizer_id', $userId)->findOrFail($id);

        $this->authorize('delete', $webhook);

        $webhook->delete();

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'webhook_deleted',
            'target_type' => Webhook::class,
            'target_id' => $webhook->id,
            'status' => 'success',
            'source' => 'developer_portal',
            'ip_address' => $request->ip(),
            'metadata' => ['url' => $webhook->url],
        ]);

        return response()->json(null, 204);
    }
}

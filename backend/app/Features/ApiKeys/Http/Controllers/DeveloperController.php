<?php

namespace App\Features\ApiKeys\Http\Controllers;

use App\Features\ApiKeys\Resources\ApiKeyResource;
use App\Features\ApiKeys\Requests\StoreApiKeyRequest;
use App\Features\ApiKeys\Services\ApiKeyService;
use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\Webhook;
use App\Models\WebhookDeliveryLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * Developer portal controller for organizers managing API keys, webhooks,
 * and API usage logs. All endpoints are organizer-scoped via the
 * `role:organizer` route middleware plus organizer-owner filters below.
 */
class DeveloperController extends Controller
{
    public function __construct(private ApiKeyService $apiKeyService)
    {
    }

    /**
     * GET /api/developer/api-keys
     */
    public function listApiKeys(): JsonResponse
    {
        $organizer = $this->organizer();

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        $keys = ApiKey::where('organizer_id', $organizer->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => ApiKeyResource::collection($keys),
            'message' => 'API keys loaded',
        ], 200);
    }

    /**
     * POST /api/developer/api-keys
     *
     * The full key is returned exactly once (its hash is stored via
     * ApiKeyService). The frontend must show it immediately so the user
     * can copy it — it is never retrievable again.
     */
    public function createApiKey(StoreApiKeyRequest $request): JsonResponse
    {
        $organizer = $this->organizer();

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        $result = $this->apiKeyService->generate(
            $organizer,
            $request->validated('name'),
            $request->validated('scopes', []),
            $request->validated('expires_at')
        );

        return response()->json([
            'api_key' => new ApiKeyResource($result['model']),
            'raw_key' => $result['raw_key'],
            'warning' => 'This is the only time the full key will be shown. Store it securely now.',
        ], 201);
    }

    /**
     * DELETE /api/developer/api-keys/{keyId}
     */
    public function deleteApiKey(Request $request, string $keyId): JsonResponse
    {
        $organizer = $this->organizer();

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        $apiKey = ApiKey::where('id', $keyId)->where('organizer_id', $organizer->id)->first();

        if (! $apiKey) {
            return response()->json(['message' => 'API key not found'], 404);
        }

        $this->apiKeyService->revoke($apiKey);

        return response()->json(['message' => 'API key revoked'], 200);
    }

    /**
     * Resolve the authenticated user's Organizer record, or null.
     */
    private function organizer()
    {
        return Auth::user()?->organizer;
    }

    /**
     * GET /api/developer/webhooks
     */
    public function listWebhooks(): JsonResponse
    {
        $webhooks = Webhook::where('organizer_id', Auth::id())
            ->latest()
            ->select([
                'id', 'organizer_id', 'url', 'description', 'subscribed_events',
                'status', 'failure_count', 'last_success_at', 'last_failure_at',
                'timeout_seconds', 'retry_policy', 'created_at', 'updated_at',
            ])
            ->get();

        return response()->json([
            'data' => $webhooks,
            'message' => 'Webhooks loaded',
        ], 200);
    }

    /**
     * POST /api/developer/webhooks
     */
    public function createWebhook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:500'],
            'subscribedEvents' => ['required', 'array', 'min:1'],
            'subscribedEvents.*' => ['string', 'in:order.created,order.updated,ticket.issued,ticket.checked_in,payment.succeeded,payment.failed,refund.processed,event.created,event.updated'],
        ]);

        $webhook = Webhook::create([
            'organizer_id' => Auth::id(),
            'url' => $validated['url'],
            'description' => $validated['description'] ?? null,
            'secret' => Webhook::generateSecret(),
            'subscribed_events' => $validated['subscribedEvents'],
            'status' => 'active',
            'failure_count' => 0,
            'timeout_seconds' => 10,
            'retry_policy' => ['max_attempts' => 3, 'backoff_seconds' => 60],
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'webhook_created',
            'target_type' => Webhook::class,
            'target_id' => $webhook->id,
            'status' => 'success',
            'source' => 'developer_portal',
            'ip_address' => $request->ip(),
            'metadata' => ['url' => $validated['url'], 'events' => $validated['subscribedEvents']],
        ]);

        return response()->json(
            array_merge($webhook->toArray(), ['message' => 'Webhook created']),
            201
        );
    }

    /**
     * DELETE /api/developer/webhooks/{id}
     */
    public function deleteWebhook(Request $request, string $id): JsonResponse
    {
        $webhook = Webhook::where('organizer_id', Auth::id())->find($id);

        if (! $webhook) {
            return response()->json(['message' => 'Webhook not found'], 404);
        }

        $webhook->delete();

        return response()->json(['message' => 'Webhook deleted'], 200);
    }

    /**
     * GET /api/developer/api-logs
     */
    public function listApiLogs(Request $request): JsonResponse
    {
        $logs = AuditLog::where('user_id', Auth::id())
            ->latest()
            ->limit(50)
            ->get();

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
            'message' => 'API logs loaded',
        ], 200);
    }
}
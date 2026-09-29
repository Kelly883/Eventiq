<?php

namespace App\Features\ApiKeys\Controllers;

use App\Features\ApiKeys\Requests\StoreApiKeyRequest;
use App\Features\ApiKeys\Resources\ApiKeyResource;
use App\Features\ApiKeys\Services\ApiKeyService;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function __construct(private ApiKeyService $apiKeyService)
    {
    }

    /**
     * GET /api/developer/api-keys
     */
    public function index(Request $request): JsonResponse
    {
        $organizer = $request->user()?->organizer;

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        $perPage = $this->perPage($request);

        $keys = ApiKey::where('organizer_id', $organizer->id)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => ApiKeyResource::collection($keys),
            'meta' => [
                'current_page' => $keys->currentPage(),
                'last_page' => $keys->lastPage(),
                'per_page' => $keys->perPage(),
                'total' => $keys->total(),
            ],
            'links' => [
                'first' => $keys->url(1),
                'last' => $keys->url($keys->lastPage()),
                'prev' => $keys->previousPageUrl(),
                'next' => $keys->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * POST /api/developer/api-keys
     *
     * Returns the raw key in the response body exactly once - it is
     * never retrievable again after this response (only its hash is
     * stored). The frontend must display and let the user copy it here.
     */
    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $organizer = $request->user()?->organizer;

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        $result = $this->apiKeyService->generate(
            $organizer,
            $request->validated('name'),
            $request->validated('scopes', []),
            $request->validated('expires_at')
        );

        $apiKey = $result['model'];

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'api_key_created',
            'target_type' => ApiKey::class,
            'target_id' => $apiKey->id,
            'status' => 'success',
            'source' => 'developer_portal',
            'ip_address' => $request->ip(),
            'metadata' => ['name' => $apiKey->name, 'scopes' => $apiKey->scopes],
        ]);

        return response()->json([
            'api_key' => new ApiKeyResource($apiKey),
            'raw_key' => $result['raw_key'],
            'warning' => 'This is the only time the full key will be shown. Store it securely now.',
        ], 201);
    }

    /**
     * DELETE /api/developer/api-keys/{keyId}
     */
    public function destroy(Request $request, string $keyId): JsonResponse
    {
        $organizer = $request->user()?->organizer;

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        $apiKey = ApiKey::where('id', $keyId)
            ->where('organizer_id', $organizer->id)
            ->firstOrFail();

        $this->apiKeyService->revoke($apiKey);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'api_key_revoked',
            'target_type' => ApiKey::class,
            'target_id' => $apiKey->id,
            'status' => 'success',
            'source' => 'developer_portal',
            'ip_address' => $request->ip(),
            'metadata' => ['name' => $apiKey->name],
        ]);

        return response()->json(null, 204);
    }
}

<?php

namespace App\Features\ApiKeys\Controllers;

use App\Features\ApiKeys\Requests\StoreApiKeyRequest;
use App\Features\ApiKeys\Resources\ApiKeyResource;
use App\Features\ApiKeys\Services\ApiKeyService;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function __construct(private ApiKeyService $apiKeyService)
    {
    }

    /**
     * GET /api/organizer/api-keys
     */
    public function index(Request $request)
    {
        $organizer = $request->user()->organizer;

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        return ApiKeyResource::collection($organizer->apiKeys()->latest()->get());
    }

    /**
     * POST /api/organizer/api-keys
     *
     * Returns the raw key in the response body exactly once - it is
     * never retrievable again after this response (only its hash is
     * stored). The frontend must display and let the user copy it here.
     */
    public function store(StoreApiKeyRequest $request)
    {
        $organizer = $request->user()->organizer;

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
     * DELETE /api/organizer/api-keys/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $organizer = $request->user()->organizer;
        $apiKey = ApiKey::where('id', $id)->where('organizer_id', $organizer->id)->firstOrFail();

        $this->apiKeyService->revoke($apiKey);

        return response()->noContent();
    }
}

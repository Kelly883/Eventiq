<?php

namespace App\Http\Controllers\Developer;

use App\Features\ApiKeys\Requests\StoreApiKeyRequest;
use App\Features\ApiKeys\Resources\ApiKeyResource;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organizer = $request->user()?->organizer;

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        $keys = ApiKey::where('organizer_id', $organizer->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => ApiKeyResource::collection($keys),
            'message' => 'API keys loaded',
        ], 200);
    }

    public function store(Request $request, StoreApiKeyRequest $req): JsonResponse
    {
        $organizer = $request->user()?->organizer;

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        $rawKey = ApiKey::generateKey();
        $prefix = substr($rawKey, 0, 8);

        $apiKey = ApiKey::create([
            'organizer_id' => $organizer->id,
            'name' => $req->validated('name'),
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'key_hash_index' => hash('sha256', $rawKey),
            'scopes' => $req->validated('scopes', []),
            'expires_at' => $req->validated('expires_at'),
        ]);

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
            'raw_key' => $rawKey,
            'warning' => 'This is the only time the full key will be shown. Store it securely now.',
        ], 201);
    }

    public function destroy(Request $request, string $keyId): JsonResponse
    {
        $organizer = $request->user()?->organizer;

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        $apiKey = ApiKey::where('id', $keyId)
            ->where('organizer_id', $organizer->id)
            ->firstOrFail();

        $this->authorize('revoke', $apiKey);

        $apiKey->update(['revoked_at' => now()]);

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

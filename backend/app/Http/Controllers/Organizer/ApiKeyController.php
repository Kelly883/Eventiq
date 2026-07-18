<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organizer = $request->user()->organizer;
        abort_unless($organizer, 404, 'Organizer profile not found.');
        Gate::authorize('viewAny', [ApiKey::class, $organizer]);

        $apiKeys = ApiKey::query()
            ->where('organizer_id', $organizer->id)
            ->latest()
            ->get()
            ->map(fn (ApiKey $apiKey): array => $this->serializeApiKey($apiKey));

        return response()->json(['data' => $apiKeys]);
    }

    public function store(Request $request, ApiKeyService $apiKeyService): JsonResponse
    {
        $organizer = $request->user()->organizer;
        abort_unless($organizer, 404, 'Organizer profile not found.');
        Gate::authorize('create', [ApiKey::class, $organizer]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['required', 'string', Rule::in(array_keys(config('api-keys.scopes', [])))],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $result = $apiKeyService->create(
            $organizer,
            $validated['name'],
            $validated['scopes'],
            isset($validated['expires_at']) ? new \DateTimeImmutable($validated['expires_at']) : null
        );

        return response()->json([
            'data' => $this->serializeApiKey($result['api_key']),
            'raw_key' => $result['raw_key'],
            'message' => 'Copy this API key now. It will not be shown again.',
        ], 201);
    }

    public function revoke(Request $request, ApiKey $apiKey, ApiKeyService $apiKeyService): JsonResponse
    {
        Gate::authorize('revoke', $apiKey);

        $apiKeyService->revoke($apiKey, $request->user()->id);

        return response()->json(['data' => $this->serializeApiKey($apiKey->refresh())]);
    }

    public function scopes(): JsonResponse
    {
        return response()->json(['data' => config('api-keys.scopes', [])]);
    }

    private function serializeApiKey(ApiKey $apiKey): array
    {
        return [
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'key_prefix' => $apiKey->key_prefix,
            'scopes' => $apiKey->scopes ?? [],
            'revoked_at' => $apiKey->revoked_at?->toISOString(),
            'expires_at' => $apiKey->expires_at?->toISOString(),
            'last_used_at' => $apiKey->last_used_at?->toISOString(),
            'created_at' => $apiKey->created_at?->toISOString(),
        ];
    }
}

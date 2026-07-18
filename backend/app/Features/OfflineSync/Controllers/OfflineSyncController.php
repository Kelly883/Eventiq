<?php

namespace App\Features\OfflineSync\Controllers;

use App\Features\OfflineSync\Services\OfflineSyncEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class OfflineSyncController
{
    /**
     * Read the pseudonymous device identifier used for sync attribution and
     * idempotency only. Authentication and authorization remain the
     * responsibility of the auth:sanctum route middleware.
     */
    private function deviceToken(Request $request): string
    {
        $data = ['X-Device-Token' => $request->header('X-Device-Token')];

        Validator::make($data, [
            'X-Device-Token' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/i'],
        ])->validate();

        return strtolower($data['X-Device-Token']);
    }

    public function enqueue(Request $request)
    {
        $deviceToken = $this->deviceToken($request);

        $data = $request->validate([
            'client_id' => ['nullable', 'string'],
            'op_type' => ['required', 'string'],
            'entity_id' => ['required', 'string'],
            'client_mutation_id' => ['required', 'string'],
            'payload' => ['required', 'array'],
            'client_context' => ['nullable', 'array'],
        ]);

        if (isset($data['client_id']) && strtolower($data['client_id']) !== $deviceToken) {
            return response()->json([
                'message' => 'The client_id must match the X-Device-Token header.',
            ], 422);
        }

        $engine = new OfflineSyncEngine();
        $item = $engine->enqueue(
            $deviceToken,
            $data['op_type'],
            $data['entity_id'],
            $data['client_mutation_id'],
            $data['payload'],
            Arr::get($data, 'client_context')
        );

        return response()->json([
            'id' => $item->id,
            'status' => $item->status,
            'idempotency' => [
                'client_id' => $item->client_id,
                'op_type' => $item->op_type,
                'entity_id' => $item->entity_id,
                'client_mutation_id' => $item->client_mutation_id,
            ],
        ]);
    }

    public function applyDue(Request $request)
    {
        $this->deviceToken($request);

        $limit = (int) $request->query('limit', 50);
        $engine = new OfflineSyncEngine();
        $results = $engine->applyDueQueue($limit);

        return response()->json(['results' => $results]);
    }
}


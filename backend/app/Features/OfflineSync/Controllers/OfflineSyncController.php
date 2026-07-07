<?php

namespace App\Features\OfflineSync\Controllers;

use App\Features\OfflineSync\Services\OfflineSyncEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OfflineSyncController
{
    public function enqueue(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'string'],
            'op_type' => ['required', 'string'],
            'entity_id' => ['required', 'string'],
            'client_mutation_id' => ['required', 'string'],
            'payload' => ['required', 'array'],
            'client_context' => ['nullable', 'array'],
        ]);

        $engine = new OfflineSyncEngine();
        $item = $engine->enqueue(
            $data['client_id'],
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
        $limit = (int) $request->query('limit', 50);
        $engine = new OfflineSyncEngine();
        $results = $engine->applyDueQueue($limit);

        return response()->json(['results' => $results]);
    }
}


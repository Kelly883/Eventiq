<?php

namespace App\Features\OfflineSync\Controllers;

use App\Features\OfflineSync\Resources\OfflineSyncResponse;
use App\Features\OfflineSync\Resources\OfflineTicketResource;
use App\Features\OfflineSync\Services\OfflineSyncEngine;
use App\Features\PushNotifications\Models\PushNotificationDevice;
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

    public function sync(Request $request)
    {
        $deviceToken = $this->deviceToken($request);

        $device = PushNotificationDevice::where('token', $deviceToken)
            ->where('offline_enabled', true)
            ->firstOrFail();

        $user = $device->user;
        $lastSyncAt = $request->query('last_sync_at');
        $syncVersion = (int) $request->query('sync_version', 0);

        $eventsQuery = \App\Models\Event::where('organizer_id', $user->id)
            ->orWhere('user_id', $user->id);

        $eventIds = $eventsQuery->pluck('id')->all();

        $ticketsQuery = \App\Features\Checkout\Models\Ticket::whereIn('event_id', $eventIds)
            ->with(['event', 'event.organizer', 'ticketTier', 'order']);

        if ($lastSyncAt) {
            $ticketsQuery->where('updated_at', '>', $lastSyncAt);
        }

        $tickets = $ticketsQuery->get();

        $ticketResources = OfflineTicketResource::collection($tickets)->toArray($request);

        $deletedTicketIds = [];
        if ($lastSyncAt) {
            $deletedTicketIds = \App\Features\Checkout\Models\Ticket::whereIn('event_id', $eventIds)
                ->whereIn('status', ['void', 'cancelled'])
                ->where('updated_at', '>', $lastSyncAt)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();
        }

        $payload = [
            'tickets' => $ticketResources,
            'syncVersion' => $syncVersion + 1,
            'deletedTicketIds' => $deletedTicketIds,
        ];

        return new OfflineSyncResponse($payload);
    }
}


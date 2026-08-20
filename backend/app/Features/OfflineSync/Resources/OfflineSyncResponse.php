<?php

namespace App\Features\OfflineSync\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OfflineSyncResponse extends JsonResource
{
    public function toArray($request): array
    {
        $tickets = $this->resource['tickets'] ?? [];
        $grouped = [];

        foreach ($tickets as $ticket) {
            $eventId = $ticket['eventId'] ?? 'unknown';
            $grouped[$eventId][] = $ticket;
        }

        return [
            'tickets' => $grouped,
            'lastSyncedAt' => now()->toIso8601String(),
            'isSyncing' => false,
            'syncError' => null,
            'syncVersion' => $this->resource['syncVersion'] ?? null,
            'serverTime' => now()->toIso8601String(),
            'deletedTicketIds' => $this->resource['deletedTicketIds'] ?? [],
        ];
    }
}

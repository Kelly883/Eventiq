<?php

namespace App\Features\OfflineSync\Services;

use App\Features\OfflineSync\Models\OfflineSyncInboxItem;
use Illuminate\Support\Facades\DB;

class OfflineSyncEngine
{
    public function enqueue(string $clientId, string $opType, string $entityId, string $clientMutationId, array $payload, ?array $clientContext = null): OfflineSyncInboxItem
    {
        // Upsert by unique idempotency key. If already exists, do nothing.
        $item = OfflineSyncInboxItem::query()->firstOrCreate(
            [
                'client_id' => $clientId,
                'op_type' => $opType,
                'entity_id' => $entityId,
                'client_mutation_id' => $clientMutationId,
            ],
            [
                'status' => 'queued',
                'attempts' => 0,
                'payload' => $payload,
                'client_context' => $clientContext,
            ]
        );

        return $item;
    }

    public function applyDueQueue(int $limit = 50): array
    {
        $items = OfflineSyncInboxItem::query()
            ->whereIn('status', ['queued', 'conflict'])
            ->where(function ($q) {
                $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now());
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $results = [];

        foreach ($items as $item) {
            $results[] = $this->applySingle($item);
        }

        return $results;
    }

    public function applySingle(OfflineSyncInboxItem $item): array
    {
        // Ensure idempotency across retries: single record, transactional apply.
        return DB::transaction(function () use ($item) {
            // Lock row for update.
            $locked = OfflineSyncInboxItem::query()->where('id', $item->id)->lockForUpdate()->first();
            if (!$locked) {
                return ['status' => 'missing'];
            }

            if ($locked->status === 'applied') {
                return ['status' => 'applied_noop', 'id' => $locked->id];
            }

            $locked->status = 'processing';
            $locked->attempts = (int) $locked->attempts + 1;
            $locked->save();

            try {
                // Domain workflows registration is out of scope for this skeleton.
                // For now we mark applied to keep the engine runnable.
                $locked->applied_at = now();
                $locked->status = 'applied';
                $locked->error_message = null;
                $locked->save();

                return ['status' => 'applied', 'id' => $locked->id];
            } catch (\Throwable $e) {
                $locked->status = 'failed';
                $locked->error_message = $e->getMessage();
                $locked->save();

                return ['status' => 'failed', 'id' => $locked->id, 'error' => $e->getMessage()];
            }
        });
    }
}


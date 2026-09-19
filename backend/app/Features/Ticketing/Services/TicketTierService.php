<?php

namespace App\Features\Ticketing\Services;

use App\Models\TicketTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketTierService
{
    /**
     * Sync ticket tiers for an event in a transaction.
     * - Update existing tiers (with id)
     * - Create new tiers (without id)
     * - Delete tiers not in request (soft delete)
     *
     * @param int|string $eventId
     * @param array $tiers
     * @return \Illuminate\Database\Eloquent\Collection
     */
    /**
     * Guard: prevent deleting all tiers via empty array when tiers exist.
     * Returns void or throws ValidationException.
     */
    private function assertCanDeleteAllTiers($eventId, array $tiers, string $errorKey = 'ticketTiers'): void
    {
        if (config('ticketing.allow_empty_ticket_tiers', false)) {
            return;
        }

        $hasActive = TicketTier::withTrashed()->where('event_id', $eventId)->get()->filter(fn($t) => !$t->trashed())->isNotEmpty();
        if (empty($tiers) && $hasActive) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $errorKey => ['Cannot delete all ticket tiers. At least one tier must remain.']
            ]);
        }
    }

    public function syncTiers($eventId, array $tiers, ?\App\Models\User $user = null, ?\Illuminate\Http\Request $request = null)
    {
        return DB::transaction(function () use ($eventId, $tiers, $user, $request) {
            $this->assertCanDeleteAllTiers($eventId, $tiers, 'ticketTiers');

            // Include soft-deleted for reactivation
            $existing = TicketTier::withTrashed()->where('event_id', $eventId)->lockForUpdate()->get()->keyBy('id');
            $keepIds = [];

            foreach ($tiers as $index => $tierData) {
                // Normalize camelCase to snake_case for compat
                $tierData = $this->normalizeTierData($tierData);

                $tierId = $tierData['id'] ?? null;

                if ($tierId && $existing->has($tierId)) {
                    $tier = $existing->get($tierId);
                    // Ensure tier belongs to this event (defense, though policy already checked)
                    if ((int) $tier->event_id !== (int) $eventId) {
                        throw new \Illuminate\Validation\ValidationException(
                            validator([], []),
                            response()->json(['message' => 'Ticket tier does not belong to this event'], 422)
                        );
                    }

                    $oldValues = $tier->toArray();
                    $payload = $this->mapTierDataForUpdate($tierData, $eventId, $index, $tier);
                    if ($tier->trashed()) {
                        $tier->restore();
                    }
                    $tier->update($payload);
                    $keepIds[] = $tierId;

                    // Per-tier audit log
                    if ($user) {
                        $this->logTierAudit('ticket_tier.updated', $user, $eventId, $tier, $oldValues, $tier->toArray(), $request);
                    }
                } else {
                    // Create new tier
                    $payload = $this->mapTierData($tierData, $eventId, $index);
                    // Remove id if present for new
                    unset($payload['id']);
                    $newTier = TicketTier::create($payload);
                    $keepIds[] = $newTier->id;

                    // Per-tier audit log
                    if ($user) {
                        $this->logTierAudit('ticket_tier.created', $user, $eventId, $newTier, null, $newTier->toArray(), $request);
                    }
                }
            }

            // Delete tiers not in request
            $toDelete = $existing->keys()->diff($keepIds);
            if ($toDelete->isNotEmpty()) {
                foreach ($toDelete as $deleteId) {
                    $tier = $existing->get($deleteId);
                    if ($user) {
                        $this->logTierAudit('ticket_tier.deleted', $user, $eventId, $tier, $tier->toArray(), null, $request);
                    }
                }
                TicketTier::whereIn('id', $toDelete->toArray())
                    ->where('event_id', $eventId)
                    ->delete();
            }

            // Return fresh tiers ordered by id (preserves request order as ids are sequential)
            return TicketTier::where('event_id', $eventId)->orderBy('id')->get();
        });
    }

    private function logTierAudit(string $action, \App\Models\User $user, $eventId, TicketTier $tier, ?array $oldValues, ?array $newValues, ?\Illuminate\Http\Request $request): void
    {
        try {
            \App\Services\Audit\AuditLogger::log(
                action: $action,
                user: $user,
                resourceType: 'ticket',
                resourceId: (string) $tier->id,
                description: str_replace(['ticket_tier.', 'ticket_tier_'], ['Tier ', 'Tier '], $action) . " '{$tier->name}' for event #{$eventId}",
                oldValues: $oldValues,
                newValues: $newValues,
                request: $request,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to write tier audit log', ['error' => $e->getMessage()]);
        }
    }

    private function normalizeTierData(array $data): array
    {
        // Handle camelCase
        if (isset($data['salesStartDate']) && !isset($data['sales_start_date'])) {
            $data['sales_start_date'] = $data['salesStartDate'];
        }
        if (isset($data['salesEndDate']) && !isset($data['sales_end_date'])) {
            $data['sales_end_date'] = $data['salesEndDate'];
        }
        if (isset($data['earlyBirdPrice']) && !isset($data['early_bird_price'])) {
            $data['early_bird_price'] = $data['earlyBirdPrice'];
        }
        if (isset($data['earlyBirdEndDate']) && !isset($data['early_bird_end_date'])) {
            $data['early_bird_end_date'] = $data['earlyBirdEndDate'];
        }
        if (isset($data['maxPerCustomer']) && !isset($data['max_per_customer'])) {
            $data['max_per_customer'] = $data['maxPerCustomer'];
        }
        if (isset($data['tierImageUrl']) && !isset($data['tier_image_url'])) {
            $data['tier_image_url'] = $data['tierImageUrl'];
        }
        if (isset($data['benefitsDescription']) && !isset($data['benefits_description'])) {
            $data['benefits_description'] = $data['benefitsDescription'];
        }
        if (isset($data['tierOrder']) && !isset($data['tier_order'])) {
            $data['tier_order'] = $data['tierOrder'];
        }
        return $data;
    }

    private function mapTierData(array $tierData, $eventId, int $index = 0): array
    {
        return [
            'event_id' => $eventId,
            'name' => $tierData['name'] ?? null,
            'price' => isset($tierData['price']) ? (float) $tierData['price'] : 0,
            'quantity' => isset($tierData['quantity']) ? (int) $tierData['quantity'] : null,
            'sales_start_date' => $tierData['sales_start_date'] ?? null,
            'sales_end_date' => $tierData['sales_end_date'] ?? null,
            'benefits_description' => $tierData['benefits_description'] ?? null,
            'tier_image_url' => $tierData['tier_image_url'] ?? null,
            'early_bird_price' => isset($tierData['early_bird_price']) ? (float) $tierData['early_bird_price'] : null,
            'early_bird_end_date' => $tierData['early_bird_end_date'] ?? null,
            'max_per_customer' => isset($tierData['max_per_customer']) ? (int) $tierData['max_per_customer'] : null,
            'tier_order' => $tierData['tier_order'] ?? $index,
            'is_active' => $tierData['is_active'] ?? true,
            'currency' => $tierData['currency'] ?? 'NGN',
            'status' => $tierData['status'] ?? 'published',
            // Support extra fields from base model
            'description' => $tierData['description'] ?? $tierData['benefits_description'] ?? null,
            'benefits' => $tierData['benefits'] ?? null,
        ];
    }

    private function mapTierDataForUpdate(array $tierData, $eventId, int $index, $existingTier): array
    {
        $payload = [];
        if (array_key_exists('name', $tierData)) $payload['name'] = $tierData['name'];
        if (array_key_exists('price', $tierData)) $payload['price'] = (float) $tierData['price'];
        if (array_key_exists('quantity', $tierData)) $payload['quantity'] = (int) $tierData['quantity'];
        if (array_key_exists('sales_start_date', $tierData)) $payload['sales_start_date'] = $tierData['sales_start_date'];
        if (array_key_exists('sales_end_date', $tierData)) $payload['sales_end_date'] = $tierData['sales_end_date'];
        if (array_key_exists('benefits_description', $tierData)) {
            $payload['benefits_description'] = $tierData['benefits_description'];
            $payload['description'] = $tierData['benefits_description'];
        } elseif (array_key_exists('description', $tierData)) {
            $payload['description'] = $tierData['description'];
        }
        if (array_key_exists('tier_image_url', $tierData)) $payload['tier_image_url'] = $tierData['tier_image_url'];
        if (array_key_exists('early_bird_price', $tierData)) $payload['early_bird_price'] = $tierData['early_bird_price'] !== null ? (float) $tierData['early_bird_price'] : null;
        if (array_key_exists('early_bird_end_date', $tierData)) $payload['early_bird_end_date'] = $tierData['early_bird_end_date'];
        if (array_key_exists('max_per_customer', $tierData)) $payload['max_per_customer'] = $tierData['max_per_customer'] !== null ? (int) $tierData['max_per_customer'] : null;
        if (array_key_exists('currency', $tierData)) $payload['currency'] = $tierData['currency'];
        if (array_key_exists('status', $tierData)) $payload['status'] = $tierData['status'];
        if (array_key_exists('is_active', $tierData)) $payload['is_active'] = $tierData['is_active'];
        if (array_key_exists('benefits', $tierData)) $payload['benefits'] = $tierData['benefits'];
        if (array_key_exists('tier_order', $tierData)) {
            $payload['tier_order'] = $tierData['tier_order'];
        } else {
            $payload['tier_order'] = $index;
        }
        return $payload;
    }

}

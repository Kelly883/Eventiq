<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        // Calculated fields (parity with Feature TicketTierResource)
        $isEarlyBirdActive = false;
        $effectivePrice = $data['price'] ?? null;
        try {
            if (method_exists($this->resource, 'isEarlyBirdActive')) {
                $isEarlyBirdActive = $this->resource->isEarlyBirdActive();
            } else {
                $isEarlyBirdActive = isset($data['early_bird_price'], $data['early_bird_end_date'])
                    && $data['early_bird_price'] !== null
                    && $data['early_bird_end_date'] !== null
                    && now()->isBefore($data['early_bird_end_date'])
                    && (float) $data['early_bird_price'] < (float) ($data['price'] ?? 0);
            }
            if (method_exists($this->resource, 'getEffectivePrice')) {
                $effectivePrice = $this->resource->getEffectivePrice();
            } elseif ($isEarlyBirdActive) {
                $effectivePrice = (float) $data['early_bird_price'];
            } elseif (isset($data['price'])) {
                $effectivePrice = (float) $data['price'];
            }
        } catch (\Throwable $e) {
        }

        $data['isEarlyBirdActive'] = $isEarlyBirdActive;
        $data['is_early_bird_active'] = $isEarlyBirdActive;
        $data['effectivePrice'] = $effectivePrice !== null ? (float) $effectivePrice : null;
        $data['effective_price'] = $data['effectivePrice'];
        $data['available_count'] = $this->resource->available_count ?? $data['available_count'] ?? null;
        $data['sold_count'] = $data['sold_count'] ?? 0;

        return $data;
    }
}

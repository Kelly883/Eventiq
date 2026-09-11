<?php

namespace App\Features\Ticketing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Base fields from schema
        $data = [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'price' => $this->price !== null ? (float) $this->price : null,
            'quantity' => $this->quantity,
            'sales_start_date' => $this->sales_start_date,
            'sales_end_date' => $this->sales_end_date,
            'benefits_description' => $this->benefits_description ?? $this->description ?? null,
            'tier_image_url' => $this->tier_image_url,
            'early_bird_price' => $this->early_bird_price !== null ? (float) $this->early_bird_price : null,
            'early_bird_end_date' => $this->early_bird_end_date,
            'max_per_customer' => $this->max_per_customer,
            'tier_order' => $this->tier_order,
            'is_active' => $this->is_active,
            'currency' => $this->currency ?? 'NGN',
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Calculated fields
        $isEarlyBirdActive = false;
        $effectivePrice = $data['price'];
        try {
            if (method_exists($this->resource, 'isEarlyBirdActive')) {
                $isEarlyBirdActive = $this->resource->isEarlyBirdActive();
            } else {
                // Fallback logic if method not available (feature model vs base model)
                $isEarlyBirdActive = $this->early_bird_price !== null
                    && $this->early_bird_end_date !== null
                    && now()->isBefore($this->early_bird_end_date)
                    && (float) $this->early_bird_price < (float) $this->price;
            }
            if (method_exists($this->resource, 'getEffectivePrice')) {
                $effectivePrice = $this->resource->getEffectivePrice();
            } elseif ($isEarlyBirdActive) {
                $effectivePrice = (float) $this->early_bird_price;
            }
        } catch (\Throwable $e) {
            // Keep defaults
        }

        $data['isEarlyBirdActive'] = $isEarlyBirdActive;
        $data['is_early_bird_active'] = $isEarlyBirdActive;
        $data['effectivePrice'] = $effectivePrice !== null ? (float) $effectivePrice : null;
        $data['effective_price'] = $data['effectivePrice'];

        // Extra useful fields
        $data['available_count'] = $this->available_count ?? null;
        $data['sold_count'] = $this->sold_count ?? 0;

        return $data;
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiKeyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organizerId' => $this->organizer_id,
            'name' => $this->name,
            'description' => $this->description,
            'keyPrefix' => $this->key_prefix,
            'scopes' => $this->scopes ?: [],
            'revokedAt' => $this->revoked_at?->toIso8601String(),
            'expiresAt' => $this->expires_at?->toIso8601String(),
            'lastUsedAt' => $this->last_used_at?->toIso8601String(),
            'lastUsedIp' => $this->last_used_ip,
            'rateLimit' => $this->rate_limit,
            'rateLimitPeriod' => $this->rate_limit_period,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}

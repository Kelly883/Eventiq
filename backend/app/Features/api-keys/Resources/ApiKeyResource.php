<?php

namespace App\Features\ApiKeys\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Never includes hashed_key (already $hidden on the model too - this is
 * belt-and-suspenders) or the raw key, which only ever exists transiently
 * in ApiKeyService::generate()'s return value at creation time.
 */
class ApiKeyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'key_prefix' => $this->key_prefix,
            'scopes' => $this->scopes,
            'revoked_at' => $this->revoked_at,
            'expires_at' => $this->expires_at,
            'last_used_at' => $this->last_used_at,
            'created_at' => $this->created_at,
        ];
    }
}

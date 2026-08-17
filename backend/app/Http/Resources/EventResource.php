<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['status'] = match ($this->status) {
            'published' => 'live',
            'archived' => 'past',
            default => $this->status,
        };

        return $data;
    }
}

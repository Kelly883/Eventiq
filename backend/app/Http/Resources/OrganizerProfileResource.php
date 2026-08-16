<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->getPrivateProfile();
    }
}

<?php

namespace App\Features\Analytics\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TierPerformanceResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}

<?php

namespace App\Features\Refunds\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public function toArray($request) { return parent::toArray($request); }
}

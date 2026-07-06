<?php

namespace App\Features\Payouts\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray($request) { return parent::toArray($request); }
}

<?php

namespace App\Features\CheckIn\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CheckInResource extends JsonResource
{
    public function toArray($request) { return parent::toArray($request); }
}

<?php

namespace App\Features\Delivery\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryPreferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}

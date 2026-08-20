<?php

namespace App\Features\QRCodeTicketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QRCodeResource extends JsonResource
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

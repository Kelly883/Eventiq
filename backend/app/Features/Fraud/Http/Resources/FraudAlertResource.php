<?php

namespace App\Features\Fraud\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FraudAlertResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Features\EmailNotifications\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmailTemplateResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}

<?php

namespace App\Features\PushNotifications\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PushNotificationTemplateResource extends JsonResource
{
    public function toArray($request) { return parent::toArray($request); }
}

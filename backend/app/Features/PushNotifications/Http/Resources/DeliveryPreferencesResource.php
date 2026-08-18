<?php

namespace App\Features\PushNotifications\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryPreferencesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'pushNotificationsEnabled' => (bool) $this->resource->push_notifications_enabled,
            'pushOrderConfirmation' => (bool) $this->resource->push_order_confirmation,
            'pushEventReminder' => (bool) $this->resource->push_event_reminder,
            'pushCheckinAlert' => (bool) $this->resource->push_checkin_alert,
            'pushPromotionalOffers' => (bool) $this->resource->push_promotional_offers,
        ];
    }
}

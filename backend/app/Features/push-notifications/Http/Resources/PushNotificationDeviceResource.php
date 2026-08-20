<?php

namespace App\Features\PushNotifications\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PushNotificationDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'userId' => $this->resource->user_id,
            'token' => $this->resource->token,
            'provider' => $this->resource->provider,
            'deviceType' => $this->resource->device_type,
            'deviceName' => $this->resource->device_name,
            'model' => $this->resource->model,
            'appVersion' => $this->resource->app_version,
            'osVersion' => $this->resource->os_version,
            'locale' => $this->resource->locale,
            'timezone' => $this->resource->timezone,
            'lastError' => $this->resource->last_error,
            'errorCount' => $this->resource->error_count,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }
}

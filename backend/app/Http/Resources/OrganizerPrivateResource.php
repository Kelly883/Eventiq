<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizerPrivateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'userId' => $this->userId,
            'displayName' => $this->displayName,
            'bio' => $this->bio,
            'avatarUrl' => $this->avatarUrl,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'socialLinks' => $this->socialLinks,
            'brandingColors' => $this->brandingColors,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'country' => $this->country,
            'verificationStatus' => $this->verificationStatus,
            'paymentDefault' => $this->paymentDefault,
            'commissionRate' => $this->commissionRate,
            'isPublic' => $this->isPublic,
            'emailPublic' => $this->emailPublic,
            'phonePublic' => $this->phonePublic,
            'hideSocialLinks' => $this->hideSocialLinks,
            'hideBrandingColors' => $this->hideBrandingColors,
            'notificationPreferences' => $this->notificationPreferences,
            'totalEventsCreated' => $this->totalEventsCreated,
            'totalTicketsSold' => $this->totalTicketsSold,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deletedAt' => $this->deletedAt,
        ];
    }
}

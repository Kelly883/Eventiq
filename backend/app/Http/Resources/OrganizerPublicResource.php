<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizerPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'displayName' => $this->displayName,
            'bio' => $this->bio,
            'avatarUrl' => $this->avatarUrl,
            'website' => $this->website,
            'socialLinks' => $this->when(! $this->hideSocialLinks, $this->socialLinks),
            'brandingColors' => $this->when(! $this->hideBrandingColors, $this->brandingColors),
            'totalEventsCreated' => $this->totalEventsCreated,
            'totalTicketsSold' => $this->totalTicketsSold,
            'createdAt' => $this->created_at,
            'email' => $this->when($this->emailPublic, $this->email),
            'phone' => $this->when($this->phonePublic, $this->phone),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizerPublicResource extends JsonResource
{
    /**
     * Was previously a flat, unconditional field dump identical in shape
     * to OrganizerPrivateResource -- it exposed email, phone,
     * commissionRate, and paymentDefault to every caller regardless of the
     * organizer's own isPublic/emailPublic/phonePublic/hideBrandingColors/
     * hideSocialLinks preferences, even though those exact flags exist on
     * the model specifically to gate this. The model's own
     * getPublicProfile() already implements this correctly (checked
     * directly, not assumed) -- this resource just never called it.
     * Delegating to it here instead of duplicating that logic a second
     * time in a place that's already proven easy to let drift out of sync
     * with the model.
     */
    public function toArray(Request $request): array
    {
        return $this->resource->getPublicProfile();
    }
}

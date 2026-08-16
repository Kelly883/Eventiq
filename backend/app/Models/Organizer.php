<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organizer extends Model
{
    use HasFactory, SoftDeletes;

    const DELETED_AT = 'deletedAt';

    protected $fillable = [
        'user_id',
        'business_name',
        'bio',
        'branding_color',
        'logo_path',
        'website_url',
        'social_links',
        'privacy_settings',
        'paystack_subaccount_code',
        'flutterwave_subaccount_id',
        'paystack_connect_status',
        'flutterwave_connect_status',
        'userId',
        'displayName',
        'avatarUrl',
        'email',
        'phone',
        'website',
        'socialLinks',
        'brandingColors',
        'isPublic',
        'emailPublic',
        'phonePublic',
        'notificationPreferences',
        'totalEventsCreated',
        'totalTicketsSold',
        'verified',
        'response_rate',
        'average_rating',
        'location',
        'timezone',
    ];

    protected $casts = [
        'social_links' => 'array',
        'privacy_settings' => 'array',
        'socialLinks' => 'array',
        'brandingColors' => 'array',
        'notificationPreferences' => 'array',
        'isPublic' => 'boolean',
        'emailPublic' => 'boolean',
        'phonePublic' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function payoutMethods(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Features\Payment\Models\OrganizerPayoutMethod::class);
    }

    public function apiKeys(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ApiKey::class);
    }

    public function getPublicProfile(): ?array
    {
        if (! $this->isPublic) {
            return null;
        }

        $data = [
            'id' => $this->id,
            'userId' => $this->userId,
            'displayName' => $this->displayName,
            'avatarUrl' => $this->avatarUrl,
            'logoUrl' => $this->logo_path,
            'website' => $this->website,
            'socialLinks' => $this->socialLinks,
            'brandingColors' => $this->brandingColors,
            'brandingColor' => $this->branding_color,
            'totalEventsCreated' => $this->totalEventsCreated ?? 0,
            'totalTicketsSold' => $this->totalTicketsSold ?? 0,
            'createdAt' => $this->created_at,
        ];

        if ($this->bio !== null) {
            $data['bio'] = $this->bio;
        }

        if ($this->emailPublic && $this->email !== null) {
            $data['email'] = $this->email;
        }

        if ($this->phonePublic && $this->phone !== null) {
            $data['phone'] = $this->phone;
        }

        return $data;
    }

    public function getPrivateProfile(): array
    {
        return array_merge($this->getPublicProfile(), [
            'email' => $this->email,
            'phone' => $this->phone,
            'business_name' => $this->business_name,
            'website_url' => $this->website_url,
            'social_links' => $this->social_links,
            'privacy_settings' => $this->privacy_settings,
            'notificationPreferences' => $this->notificationPreferences,
            'isPublic' => $this->isPublic,
            'emailPublic' => $this->emailPublic,
            'phonePublic' => $this->phonePublic,
            'paystack_subaccount_code' => $this->paystack_subaccount_code,
            'flutterwave_subaccount_id' => $this->flutterwave_subaccount_id,
            'paystack_connect_status' => $this->paystack_connect_status,
            'flutterwave_connect_status' => $this->flutterwave_connect_status,
            'updatedAt' => $this->updated_at,
        ]);
    }

    public function calculateStats(): void
    {
        $this->totalEventsCreated = $this->events()->count();
        $this->totalTicketsSold = $this->events()
            ->join('tickets', 'events.id', '=', 'tickets.event_id')
            ->whereIn('tickets.status', ['valid', 'checked_in'])
            ->count();
        $this->save();
    }

    public function setBrandingColorsAttribute(?array $colors): void
    {
        $this->attributes['brandingColors'] = json_encode($this->validateBrandingColors($colors));
    }

    private function validateBrandingColors(?array $colors): ?array
    {
        if ($colors === null) {
            return null;
        }

        $validated = [];

        if (isset($colors['primaryColor'])) {
            $validated['primaryColor'] = $this->validateHexColor($colors['primaryColor']);
        }

        if (isset($colors['accentColor'])) {
            $validated['accentColor'] = $this->validateHexColor($colors['accentColor']);
        }

        return $validated;
    }

    private function validateHexColor(string $color): string
    {
        if (! preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            throw new \InvalidArgumentException("Invalid hex color: {$color}");
        }

        return $color;
    }
}

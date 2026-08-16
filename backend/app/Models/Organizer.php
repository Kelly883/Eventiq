<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organizer extends Model
{
    use HasFactory, SoftDeletes;

    const DELETED_AT = 'deletedAt';

    protected $fillable = [
        'user_id',
        'userId',
        'displayName',
        'bio',
        'avatarUrl',
        'email',
        'phone',
        'website',
        'socialLinks',
        'brandingColors',
        'timezone',
        'currency',
        'country',
        'verificationStatus',
        'paymentDefault',
        'commissionRate',
        'isPublic',
        'emailPublic',
        'phonePublic',
        'hideSocialLinks',
        'hideBrandingColors',
        'notificationPreferences',
        'totalEventsCreated',
        'totalTicketsSold',
    ];

    protected $casts = [
        'socialLinks' => 'array',
        'brandingColors' => 'array',
        'notificationPreferences' => 'array',
        'isPublic' => 'boolean',
        'emailPublic' => 'boolean',
        'phonePublic' => 'boolean',
        'hideSocialLinks' => 'boolean',
        'hideBrandingColors' => 'boolean',
        'totalEventsCreated' => 'integer',
        'totalTicketsSold' => 'integer',
        'commissionRate' => 'decimal:2',
        'deletedAt' => 'datetime',
    ];

    protected $appends = [
        'isPublic',
        'emailPublic',
        'phonePublic',
        'hideSocialLinks',
        'hideBrandingColors',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function tickets(): HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Features\Checkout\Models\Ticket::class,
            Event::class,
            'organizer_id',
            'event_id',
            'id',
            'id'
        );
    }

    public function payoutMethods(): HasMany
    {
        return $this->hasMany(\App\Features\Payment\Models\OrganizerPayoutMethod::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(\App\Models\ApiKey::class);
    }

    public function setSocialLinksAttribute($value): void
    {
        if (is_array($value)) {
            $value = array_map(function ($link) {
                return $link === '' ? null : $link;
            }, $value);
        }
        $this->attributes['socialLinks'] = $value;
    }

    public function setBrandingColorsAttribute($value): void
    {
        if (is_array($value)) {
            $value = array_map(function ($color) {
                if ($color === null || $color === '') {
                    return null;
                }
                $color = strtolower(trim($color));
                if (preg_match('/^#([a-f0-9]{3})$/', $color, $matches)) {
                    $color = '#' . $matches[1][0] . $matches[1][0] . $matches[1][1] . $matches[1][1] . $matches[1][2] . $matches[1][2];
                }
                return $color;
            }, $value);
        }
        $this->attributes['brandingColors'] = $value;
    }

    public function setBioAttribute($value): void
    {
        $this->attributes['bio'] = $value !== null ? trim(substr($value, 0, 500)) : null;
    }

    public function getPublicProfile(): array
    {
        if (! $this->isPublic) {
            return [
                'id' => $this->id,
                'userId' => $this->userId,
                'displayName' => $this->displayName,
                'isPublic' => false,
                'createdAt' => $this->created_at,
            ];
        }

        $profile = [
            'id' => $this->id,
            'userId' => $this->userId,
            'displayName' => $this->displayName,
            'bio' => $this->bio,
            'avatarUrl' => $this->avatarUrl,
            'website' => $this->website,
            'totalEventsCreated' => $this->totalEventsCreated,
            'totalTicketsSold' => $this->totalTicketsSold,
            'createdAt' => $this->created_at,
        ];

        if (! $this->hideBrandingColors) {
            $profile['brandingColors'] = $this->brandingColors;
        }

        if (! $this->hideSocialLinks) {
            $profile['socialLinks'] = $this->socialLinks;
        }

        if ($this->emailPublic) {
            $profile['email'] = $this->email;
        }

        if ($this->phonePublic) {
            $profile['phone'] = $this->phone;
        }

        return $profile;
    }

    public function getPrivateProfile(): array
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

    public function recalculateStats(): void
    {
        $this->update([
            'totalEventsCreated' => $this->events()->count(),
            'totalTicketsSold' => $this->tickets()->count(),
        ]);
    }
}

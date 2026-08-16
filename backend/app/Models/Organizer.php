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

    public function getPublicProfile(): array
    {
        $data = [
            'id' => $this->id,
            'userId' => $this->userId,
            'displayName' => $this->displayName,
            'bio' => $this->bio,
            'avatarUrl' => $this->avatarUrl,
            'website' => $this->website,
            'socialLinks' => $this->socialLinks,
            'brandingColors' => $this->brandingColors,
            'totalEventsCreated' => $this->totalEventsCreated ?? 0,
            'totalTicketsSold' => $this->totalTicketsSold ?? 0,
            'createdAt' => $this->created_at,
        ];

        if ($this->emailPublic) {
            $data['email'] = $this->email;
        }

        if ($this->phonePublic) {
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
        $this->totalTicketsSold = $this->events()->join('tickets', 'events.id', '=', 'tickets.event_id')->count();
        $this->save();
    }
}

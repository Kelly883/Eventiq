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
        'isPublic',
        'emailPublic',
        'phonePublic',
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
        'totalEventsCreated' => 'integer',
        'totalTicketsSold' => 'integer',
        'deletedAt' => 'datetime',
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

    public function getPublicProfile(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'displayName' => $this->displayName,
            'bio' => $this->bio,
            'avatarUrl' => $this->avatarUrl,
            'website' => $this->website,
            'socialLinks' => $this->socialLinks,
            'brandingColors' => $this->brandingColors,
            'totalEventsCreated' => $this->totalEventsCreated,
            'totalTicketsSold' => $this->totalTicketsSold,
            'createdAt' => $this->created_at,
            ...($this->emailPublic ? ['email' => $this->email] : []),
            ...($this->phonePublic ? ['phone' => $this->phone] : []),
        ];
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
            'isPublic' => $this->isPublic,
            'emailPublic' => $this->emailPublic,
            'phonePublic' => $this->phonePublic,
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

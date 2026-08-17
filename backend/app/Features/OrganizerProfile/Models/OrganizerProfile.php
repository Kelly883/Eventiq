<?php

namespace App\Features\OrganizerProfile\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizerProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'organizers';

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Event::class, 'organizer_id');
    }
}

<?php

namespace App\Features\OrganizerProfile\Models;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizerProfile extends Organizer
{
    protected $table = 'organizers';

    protected $fillable = [
        'user_id',
        'business_name',
        'display_name',
        'bio',
        'branding_color',
        'logo_path',
        'avatar_url',
        'email',
        'phone',
        'website_url',
        'social_links',
        'privacy_settings',
        'is_public',
        'paystack_subaccount_code',
        'flutterwave_subaccount_id',
        'paystack_connect_status',
        'flutterwave_connect_status',
    ];

    protected $casts = [
        'social_links' => 'array',
        'privacy_settings' => 'array',
        'is_public' => 'boolean',
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

<?php

namespace App\Features\OrganizerProfile\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrganizerProfile extends Model
{
    use HasFactory;

    protected $table = 'organizers';

    protected $fillable = [
        'user_id',
        'business_name',
        'bio',
        'branding_color',
        'logo_path',
        'website_url',
        'social_links',
        'privacy_settings',
    ];

    protected $casts = [
        'social_links' => 'array',
        'privacy_settings' => 'array',
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
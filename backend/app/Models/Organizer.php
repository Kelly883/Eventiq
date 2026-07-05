<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organizer extends Model
{
    use HasFactory;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Event::class);
    }
}

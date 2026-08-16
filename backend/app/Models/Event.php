<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organizer_id',
        'user_id',
        'title',
        'description',
        'start_datetime',
        'end_datetime',
        'venue_name',
        'venue_address',
        'latitude',
        'longitude',
        'banner_image_url',
        'capacity',
        'status',
        'category',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'capacity' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function setVenueLatitudeAttribute($value): void
    {
        $this->attributes['latitude'] = $value;
    }

    public function getVenueLatitudeAttribute()
    {
        return $this->attributes['latitude'] ?? null;
    }

    public function setVenueLongitudeAttribute($value): void
    {
        $this->attributes['longitude'] = $value;
    }

    public function getVenueLongitudeAttribute()
    {
        return $this->attributes['longitude'] ?? null;
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticketTiers(): HasMany
    {
        return $this->hasMany(TicketTier::class);
    }

    public function pricingWindows(): HasMany
    {
        return $this->hasMany(\App\Features\Pricing\Models\PricingWindow::class);
    }

    public function scopeWhereNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }
}

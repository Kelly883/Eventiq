<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $with = ['organizer', 'analyticsEventsMetric'];

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

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if ($event->capacity === null || $event->capacity < 0) {
                throw new \InvalidArgumentException('Event capacity is required and must be a non-negative integer.');
            }
        });

        static::updating(function (Event $event) {
            if ($event->isDirty('capacity') && ($event->capacity === null || $event->capacity < 0)) {
                throw new \InvalidArgumentException('Event capacity is required and must be a non-negative integer.');
            }
        });
    }

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

    public function analyticsEventsMetric(): HasOne
    {
        return $this->hasOne(AnalyticsEventsMetric::class);
    }

    public function scopeWhereNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'published')
            ->where('start_datetime', '>', now());
    }

    public function scopeUpcomingFirst($query)
    {
        return $query->orderBy('start_datetime', 'asc');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByOrganizer($query, $organizerId)
    {
        return $query->where('organizer_id', $organizerId);
    }
}

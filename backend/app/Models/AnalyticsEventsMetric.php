<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnalyticsEventsMetric extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'analytics_events_metrics';

    protected $fillable = [
        'id',
        'event_id',
        'organizer_id',
        'total_revenue',
        'total_tickets_sold',
        'total_page_views',
        'total_ticket_page_views',
        'conversion_rate',
        'average_ticket_price',
        'peak_sales_hour',
        'top_ticket_tier_id',
        'last_updated_at',
    ];

    protected $casts = [
        'last_updated_at' => 'datetime',
        'total_revenue' => 'decimal:12',
        'average_ticket_price' => 'decimal:10',
        'conversion_rate' => 'decimal:5',
        'total_tickets_sold' => 'integer',
        'total_page_views' => 'integer',
        'total_ticket_page_views' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function topTicketTier(): BelongsTo
    {
        return $this->belongsTo(TicketTier::class, 'top_ticket_tier_id');
    }

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function getTrendAttribute(?string $direction = null): string
    {
        if ($direction === null) {
            return 'flat';
        }

        return $direction;
    }

    public function getRevenueTrendAttribute(): string
    {
        return $this->getTrendAttribute();
    }

    public function getTicketsSoldTrendAttribute(): string
    {
        return $this->getTrendAttribute();
    }

    public function getConversionRateTrendAttribute(): string
    {
        return $this->getTrendAttribute();
    }
}
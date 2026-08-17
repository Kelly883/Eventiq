<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnalyticsTierPerformance extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'analytics_tier_performance';

    protected $fillable = [
        'id',
        'event_id',
        'ticket_tier_id',
        'total_sold',
        'total_revenue',
        'average_price',
        'percentage_of_total_sales',
        'percentage_of_total_revenue',
        'conversion_rate',
        'last_updated_at',
    ];

    protected $casts = [
        'total_sold' => 'integer',
        'total_revenue' => 'decimal:12',
        'average_price' => 'decimal:10',
        'percentage_of_total_sales' => 'decimal:5',
        'percentage_of_total_revenue' => 'decimal:5',
        'conversion_rate' => 'decimal:5',
        'last_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketTier(): BelongsTo
    {
        return $this->belongsTo(TicketTier::class);
    }

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeForTier($query, $tierId)
    {
        return $query->where('ticket_tier_id', $tierId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
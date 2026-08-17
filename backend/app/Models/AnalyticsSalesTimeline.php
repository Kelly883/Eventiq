<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnalyticsSalesTimeline extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'analytics_sales_timeline';

    protected $fillable = [
        'id',
        'event_id',
        'ticket_tier_id',
        'pricing_window_id',
        'sale_timestamp',
        'quantity',
        'unit_price',
        'total_amount',
        'buyer_email',
        'source',
    ];

    protected $casts = [
        'sale_timestamp' => 'datetime',
        'quantity' => 'integer',
        'unit_price' => 'decimal:10',
        'total_amount' => 'decimal:12',
        'created_at' => 'datetime',
        'source' => \App\Enums\SaleSourceEnum::class,
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Sales timeline entries are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Sales timeline entries are immutable and cannot be deleted.');
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketTier(): BelongsTo
    {
        return $this->belongsTo(TicketTier::class);
    }

    public function pricingWindow(): BelongsTo
    {
        return $this->belongsTo(PricingWindow::class);
    }

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sale_timestamp', [$startDate, $endDate]);
    }

    public function scopeByTier($query, $tierId)
    {
        return $query->where('ticket_tier_id', $tierId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('sale_timestamp', '>=', now()->subDays($days));
    }
}
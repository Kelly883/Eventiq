<?php

namespace App\Features\Inventory\Models;

use App\Models\Event;
use App\Models\TicketTier;
use App\Features\Pricing\Models\PricingWindow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryAdjustment extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'event_id',
        'ticket_tier_id',
        'pricing_window_id',
        'organizer_id',
        'adjustment_type',
        'quantity_before',
        'quantity_after',
        'reason',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Inventory adjustments are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Inventory adjustments are immutable and cannot be deleted.');
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

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function getQuantityDeltaAttribute(): int
    {
        return $this->quantity_after - $this->quantity_before;
    }

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }
}

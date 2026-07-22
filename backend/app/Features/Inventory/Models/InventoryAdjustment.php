<?php

namespace App\Features\Inventory\Models;

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
        'quantity_delta',
        'reason',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'quantity_delta' => 'integer',
        'created_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    public function ticketTier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TicketTier::class);
    }

    public function pricingWindow(): BelongsTo
    {
        return $this->belongsTo(\App\Features\Pricing\Models\PricingWindow::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'organizer_id');
    }
}

<?php

namespace App\Features\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'ticket_tier_id',
        'pricing_window_id',
        'total_quantity',
        'sold_quantity',
        'reserved_quantity',
    ];

    protected $casts = [
        'total_quantity' => 'integer',
        'sold_quantity' => 'integer',
        'reserved_quantity' => 'integer',
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

    public function getRemainingAttribute()
    {
        return $this->total_quantity - $this->sold_quantity - $this->reserved_quantity;
    }
}

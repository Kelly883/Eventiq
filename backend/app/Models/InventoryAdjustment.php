<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryAdjustment extends Model
{
    use HasFactory;

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
}
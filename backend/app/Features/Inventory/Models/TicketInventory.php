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
        'total_allocated',
        'total_sold',
        'low_stock_threshold',
        'last_updated_at',
    ];

    protected $casts = [
        'total_allocated' => 'integer',
        'total_sold' => 'integer',
        'low_stock_threshold' => 'integer',
        'last_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    public function ticketTier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TicketTier::class);
    }

    /**
     * total_available is a virtual/generated column: total_allocated - total_sold
     */
    public function getRemainingAttribute(): int
    {
        return (int) ($this->total_available ?? ($this->total_allocated - $this->total_sold));
    }
}

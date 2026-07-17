<?php

namespace App\Features\Checkout\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'event_id',
        'user_id',
        'ticket_tier_id',
        'status',
        'qr_code',
        'checked_in',
        'checked_in_at',
        'checked_in_by',
    ];

    protected $casts = [
        'checked_in' => 'boolean',
        'checked_in_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    public function ticketTier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TicketTier::class);
    }
}

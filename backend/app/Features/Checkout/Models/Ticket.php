<?php

namespace App\Features\Checkout\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}

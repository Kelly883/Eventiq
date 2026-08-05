<?php

namespace App\Features\Checkout\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

protected $fillable = [
        'order_id',
        'payment_intent_id',
        'gateway_transaction_id',
        'amount',
        'currency',
        'status',
        'gateway',
        'idempotency_key',
        'gateway_response',
        'fees',
        'net_amount',
        'refunded_by',
        'refunded_at',
        'refund_reason',
        'settlement_id',
        'settled_at',
        'card_last_four',
        'card_brand',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'gateway_response' => 'array',
        'refunded_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

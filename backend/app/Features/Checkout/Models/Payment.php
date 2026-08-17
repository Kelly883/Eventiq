<?php

namespace App\Features\Checkout\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    /**
     * Payments use UUID primary keys.
     */
    public $incrementing = false;

    /**
     * The primary key is a UUID string, not an auto-increment integer.
     */
    protected $keyType = 'string';

    /**
     * Boot the model to auto-generate UUIDs for new records.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payment $payment) {
            if (empty($payment->{$payment->getKeyName()})) {
                $payment->{$payment->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'order_id',
        'user_id',
        'payment_intent_id',
        'gateway_transaction_id',
        'gateway_reference',
        'amount',
        'currency',
        'status',
        'gateway',
        'payment_channel',
        'idempotency_key',
        'gateway_response',
        'fees',
        'net_amount',
        'refunded_amount',
        'is_fully_refunded',
        'refunded_by',
        'refunded_at',
        'refund_reason',
        'settlement_id',
        'settled_at',
        'card_last_four',
        'card_brand',
        'attempts',
        'last_error',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'gateway_response' => 'array',
        'refunded_at' => 'datetime',
        'settled_at' => 'datetime',
        'is_fully_refunded' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'succeeded';
    }
}

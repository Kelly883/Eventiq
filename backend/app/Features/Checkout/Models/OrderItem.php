<?php

namespace App\Features\Checkout\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * Order items use UUID primary keys.
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

        static::creating(function (OrderItem $orderItem) {
            if (empty($orderItem->{$orderItem->getKeyName()})) {
                $orderItem->{$orderItem->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = ['order_id', 'ticket_tier_id', 'quantity', 'unit_price'];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function ticketTier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TicketTier::class);
    }

    public function getLineTotal(): string
    {
        return number_format((float) $this->quantity * (float) $this->unit_price, 2);
    }

    public function getLineTotalAmount(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}

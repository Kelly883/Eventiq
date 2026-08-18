<?php

namespace App\Features\Checkout\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    /**
     * Orders use UUID primary keys.
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

        static::creating(function (Order $order) {
            if (empty($order->{$order->getKeyName()})) {
                $order->{$order->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'event_id',
        'status',
        'total_amount',
        'currency',
        'payment_gateway',
        'payment_intent_id',
        'gateway_transaction_id',
        'device_id',
        'ip_address',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'coupon_code',
        'billing_name',
        'billing_email',
        'billing_phone',
        'failure_reason',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(\App\Features\Refunds\Models\RefundRequest::class);
    }

    public function getFormattedTotal(): string
    {
        $amount = (float) ($this->total_amount ?? 0);
        $currency = strtoupper($this->currency ?? 'NGN');

        return match ($currency) {
            'NGN' => '₦' . number_format($amount, 2),
            'USD' => '$' . number_format($amount, 2),
            'EUR' => '€' . number_format($amount, 2),
            'GBP' => '£' . number_format($amount, 2),
            'GHS' => '₵' . number_format($amount, 2),
            'KES' => 'KSh' . number_format($amount, 2),
            'ZAR' => 'R' . number_format($amount, 2),
            default => $currency . ' ' . number_format($amount, 2),
        };
    }

    public function canBeRefunded(): bool
    {
        return $this->status === 'completed';
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}

<?php

namespace App\Features\Checkout\Models;

use App\Features\Payment\Enums\PaymentGateway;
use App\Features\Payment\Enums\PaymentStatus;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
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
        'organizer_id',
        'event_id',
        'ticket_id',
        'payment_intent_id',
        'gateway_transaction_id',
        'gateway_reference',
        'authorization_code',
        'authorization_type',
        'amount',
        'currency',
        'status',
        'gateway',
        'payment_channel',
        'idempotency_key',
        'customer_email',
        'customer_code',
        'gateway_response',
        'fees',
        'net_amount',
        'refunded_amount',
        'is_fully_refunded',
        'refunded_by',
        'refunded_at',
        'refund_reason',
        'refund_reference',
        'settlement_id',
        'settled_at',
        'card_last_four',
        'card_brand',
        'last_error',
        'webhook_event_id',
        'webhook_idempotency_key',
        'paid_at',
        'attempts',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'gateway_response' => 'array',
        'refunded_at' => 'datetime',
        'settled_at' => 'datetime',
        'paid_at' => 'datetime',
        'is_fully_refunded' => 'boolean',
        'gateway' => PaymentGateway::class,
        'status' => PaymentStatus::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(\App\Features\Checkout\Models\Ticket::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::SUCCESS;
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', PaymentStatus::SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::FAILED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::PROCESSING, PaymentStatus::INITIATED]);
    }

    public function scopeForOrganizer($query, string $organizerId)
    {
        return $query->where('organizer_id', $organizerId);
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRefunded($query)
    {
        return $query->whereIn('status', [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED]);
    }

    public function getAmountInMajorUnits(): float
    {
        return (float) $this->amount;
    }

    public function getFeesInMajorUnits(): float
    {
        return (float) $this->fees;
    }

    public function getNetAmountInMajorUnits(): float
    {
        return (float) $this->net_amount;
    }
}

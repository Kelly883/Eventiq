<?php

namespace App\Features\Payment\Models;

use App\Features\Payment\Enums\PaymentGateway;
use App\Features\Payment\Enums\PaymentStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'id',
        'user_id',
        'organizer_id',
        'order_id',
        'event_id',
        'ticket_id',
        'gateway',
        'reference',
        'gateway_transaction_id',
        'gateway_reference',
        'authorization_code',
        'authorization_type',
        'amount',
        'currency',
        'fees',
        'net_amount',
        'status',
        'payment_channel',
        'customer_email',
        'customer_code',
        'gateway_response',
        'last_error',
        'paid_at',
        'refunded_amount',
        'refund_reference',
        'is_fully_refunded',
        'webhook_event_id',
        'webhook_idempotency_key',
        'attempts',
    ];

    protected $casts = [
        'gateway' => PaymentGateway::class,
        'status' => PaymentStatus::class,
        'amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'is_fully_refunded' => 'boolean',
        'paid_at' => 'datetime',
        'gateway_response' => 'array',
    ];

    protected $hidden = [
        'gateway_response',
        'authorization_code',
        'customer_code',
        'last_error',
        'webhook_event_id',
        'webhook_idempotency_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(\App\Features\Checkout\Models\Ticket::class);
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

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeRefunded($query)
    {
        return $query->whereIn('status', [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED]);
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [PaymentStatus::PENDING, PaymentStatus::PROCESSING, PaymentStatus::INITIATED], true);
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, [PaymentStatus::REFUNDED, PaymentStatus::PARTIALLY_REFUNDED], true);
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

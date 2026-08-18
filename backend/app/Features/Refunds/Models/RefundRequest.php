<?php

namespace App\Features\Refunds\Models;

use App\Features\Checkout\Models\Ticket;
use App\Models\Event;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefundRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'order_id',
        'user_id',
        'event_id',
        'original_amount',
        'refund_amount',
        'refund_percentage',
        'reason',
        'explanation',
        'refund_method',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'processing_started_at',
        'completed_at',
        'payment_gateway_refund_id',
        'payment_gateway_response',
        'appeal_count',
        'last_appeal_at',
        'payment_gateway',
        'payment_intent_id',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'refund_percentage' => 'decimal:2',
        'appeal_count' => 'integer',
        'payment_gateway_response' => 'array',
        'approved_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_appeal_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function refundPolicy(): BelongsTo
    {
        return $this->belongsTo(RefundPolicy::class, 'event_id');
    }

    public function appeals(): HasMany
    {
        return $this->hasMany(RefundAppeal::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(\App\Features\Checkout\Models\Payment::class);
    }

    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format((float) $this->original_amount, 2);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'amber',
            'approved' => 'green',
            'rejected' => 'red',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    public function getIsEligibleForAppealAttribute(): bool
    {
        return $this->status === 'rejected' && $this->appeal_count < 3;
    }

    public function getFormattedRefundAmountAttribute(): string
    {
        return '$' . number_format((float) $this->refund_amount, 2);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing'], true);
    }

    public function getRemainingAppealAttemptsAttribute(): int
    {
        return max(0, 3 - (int) $this->appeal_count);
    }

    public function getFormattedPercentageAttribute(): string
    {
        return number_format((float) $this->refund_percentage, 0) . '%';
    }

    public function scopeByPaymentGateway($query, string $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    public function scopeByRefundMethod($query, string $method)
    {
        return $query->where('refund_method', $method);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByEvent($query, string $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}

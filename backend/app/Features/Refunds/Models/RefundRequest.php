<?php

namespace App\Features\Refunds\Models;

use App\Features\Checkout\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id', 'order_id', 'user_id', 'event_id',
        'original_amount', 'refund_amount', 'refund_percentage',
        'reason', 'explanation', 'refund_method', 'status',
        'rejection_reason', 'approved_by', 'approved_at',
        'processing_started_at', 'completed_at',
        'payment_gateway_refund_id', 'payment_gateway_response',
        'appeal_count', 'last_appeal_at',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'refund_percentage' => 'decimal:2',
        'approved_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_appeal_at' => 'datetime',
        'payment_gateway_response' => 'array',
        'appeal_count' => 'integer',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class);
    }

    public function refundPolicy(): BelongsTo
    {
        return $this->belongsTo(\App\Features\Refunds\Models\RefundPolicy::class);
    }
}

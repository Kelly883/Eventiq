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
        'ticket_id', 'user_id', 'refund_policy_id', 'status',
        'requested_amount', 'approved_amount', 'reason', 'admin_notes',
        'reviewed_at', 'reviewed_by',
        'payment_gateway_refund_id', 'payment_gateway_response',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'payment_gateway_response' => 'array',
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
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function refundPolicy(): BelongsTo
    {
        return $this->belongsTo(\App\Features\Refunds\Models\RefundPolicy::class);
    }
}

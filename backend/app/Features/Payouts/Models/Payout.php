<?php

namespace App\Features\Payouts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'settlement_period_start_date',
        'settlement_period_end_date',
        'gross_revenue',
        'refunds_deducted',
        'net_revenue',
        'platform_commission_percentage',
        'platform_commission_amount',
        'processing_fee_percentage',
        'processing_fee_amount',
        'tax_withholding_percentage',
        'tax_withholding_amount',
        'payout_amount',
        'payout_method',
        'payment_gateway_payout_id',
        'payment_gateway_response',
        'status',
        'calculated_at',
        'approved_by',
        'approved_at',
        'processing_started_at',
        'completed_at',
        'failure_reason',
        'retry_count',
        'next_retry_at',
    ];

    protected $casts = [
        'gross_revenue' => 'decimal:2',
        'refunds_deducted' => 'decimal:2',
        'net_revenue' => 'decimal:2',
        'platform_commission_percentage' => 'decimal:2',
        'platform_commission_amount' => 'decimal:2',
        'processing_fee_percentage' => 'decimal:2',
        'processing_fee_amount' => 'decimal:2',
        'tax_withholding_percentage' => 'decimal:2',
        'tax_withholding_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'calculated_at' => 'datetime',
        'approved_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'payment_gateway_response' => 'array',
        'retry_count' => 'integer',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organizer::class);
    }

    public function settlementPolicy(): BelongsTo
    {
        return $this->belongsTo(SettlementPolicy::class);
    }

    public function calculation(): HasOne
    {
        return $this->hasOne(PayoutCalculation::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function markAsProcessing(): void
    {
        $this->status = self::STATUS_PROCESSING;
        $this->save();
    }

    public function markAsCompleted(string $transactionId): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->payment_gateway_payout_id = $transactionId;
        $this->completed_at = now();
        $this->save();
    }

    public function markAsFailed(string $notes = null): void
    {
        $this->status = self::STATUS_FAILED;
        if ($notes) {
            $this->failure_reason = $notes;
        }
        $this->save();
    }
}

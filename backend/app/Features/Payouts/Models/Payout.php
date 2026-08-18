<?php

namespace App\Features\Payouts\Models;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payout extends Model
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

        static::saving(function ($model) {
            if ($model->payout_amount < 0) {
                $model->payout_amount = 0;
            }
        });
    }

    protected $fillable = [
        'id',
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
        'currency',
        'payout_method',
        'payout_method_details',
        'payment_gateway_payout_id',
        'payment_gateway_response',
        'status',
        'calculated_at',
        'initiated_by',
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
        'payout_method_details' => 'array',
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
        return $this->belongsTo(Organizer::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function calculations(): HasMany
    {
        return $this->hasMany(PayoutCalculation::class);
    }

    public function scopeByOrganizer($query, string $organizerId)
    {
        return $query->where('organizer_id', $organizerId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('settlement_period_start_date', [$startDate, $endDate]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function canRetry(): bool
    {
        return $this->isFailed()
            && $this->retry_count < 3
            && $this->next_retry_at !== null
            && $this->next_retry_at->isPast();
    }

    public function calculateNetAmount(): string
    {
        return '$' . number_format((float) $this->payout_amount, 2);
    }
}

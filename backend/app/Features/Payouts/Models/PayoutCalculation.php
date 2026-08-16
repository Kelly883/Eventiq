<?php

namespace App\Features\Payouts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutCalculation extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::updating(function ($model) {
            throw new \RuntimeException('payout_calculations is append-only and cannot be updated.');
        });

        static::deleting(function ($model) {
            throw new \RuntimeException('payout_calculations is append-only and cannot be deleted.');
        });
    }

    protected $fillable = [
        'payout_id',
        'organizer_id',
        'settlement_period_start_date',
        'settlement_period_end_date',
        'event_ids',
        'order_ids',
        'refund_request_ids',
        'total_order_count',
        'total_tickets_sold',
        'total_refunds_processed',
        'calculation_details',
        'calculated_at',
        'calculated_by',
        'created_at',
    ];

    protected $casts = [
        'event_ids' => 'array',
        'order_ids' => 'array',
        'refund_request_ids' => 'array',
        'calculation_details' => 'array',
        'calculated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organizer::class);
    }

    public function calculateNetPayout(): float
    {
        return (float) $this->net_revenue - (float) $this->refund_amount;
    }

    public function getPlatformFeePercentage(): float
    {
        if ($this->gross_revenue <= 0) {
            return 0;
        }
        return ((float) $this->platform_commission_amount / (float) $this->gross_revenue) * 100;
    }
}

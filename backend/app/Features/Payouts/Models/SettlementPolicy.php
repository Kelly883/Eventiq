<?php

namespace App\Features\Payouts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SettlementPolicy extends Model
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
        'organizer_tier',
        'organizer_id',
        'platform_commission_percentage',
        'processing_fee_percentage',
        'payout_frequency',
        'minimum_payout_threshold',
        'payout_hold_days',
        'requires_approval',
        'auto_approve_threshold',
        'max_retries',
        'retry_backoff_multiplier',
        'tax_withholding_percentage',
        'allowed_payout_methods',
        'is_active',
    ];

    protected $casts = [
        'platform_commission_percentage' => 'decimal:2',
        'processing_fee_percentage' => 'decimal:2',
        'minimum_payout_threshold' => 'decimal:2',
        'auto_approve_threshold' => 'decimal:2',
        'retry_backoff_multiplier' => 'decimal:3',
        'tax_withholding_percentage' => 'decimal:2',
        'allowed_payout_methods' => 'array',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_BIWEEKLY = 'biweekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_ON_DEMAND = 'on_demand';

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organizer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTier($query, string $tier)
    {
        return $query->where('organizer_tier', $tier);
    }

    public function canAutoApprove(float $amount): bool
    {
        return $amount <= (float) $this->auto_approve_threshold;
    }

    public function calculatePlatformFee(float $amount): float
    {
        return ($amount * (float) $this->platform_commission_percentage) / 100;
    }

    public function meetsMinimumPayout(float $amount): bool
    {
        return $amount >= (float) $this->minimum_payout_threshold;
    }

    public function getFrequencyLabel(): string
    {
        $labels = [
            self::FREQUENCY_DAILY => 'Daily',
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_BIWEEKLY => 'Bi-weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_ON_DEMAND => 'On Demand',
        ];

        return $labels[$this->payout_frequency] ?? $this->payout_frequency;
    }
}

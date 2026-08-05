<?php

namespace App\Features\Payouts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SettlementPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'platform_fee_percentage',
        'payout_frequency',
        'minimum_payout_amount',
        'payment_methods',
        'is_active',
    ];

    protected $casts = [
        'platform_fee_percentage' => 'decimal:2',
        'minimum_payout_amount' => 'decimal:2',
        'payment_methods' => 'array',
        'is_active' => 'boolean',
    ];

    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_BIWEEKLY = 'biweekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_MANUAL = 'manual';

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function calculatePlatformFee(float $amount): float
    {
        return ($amount * (float) $this->platform_fee_percentage) / 100;
    }

    public function meetsMinimumPayout(float $amount): bool
    {
        return $amount >= (float) $this->minimum_payout_amount;
    }

    public function getFrequencyLabel(): string
    {
        $labels = [
            self::FREQUENCY_DAILY => 'Daily',
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_BIWEEKLY => 'Bi-weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_MANUAL => 'Manual',
        ];

        return $labels[$this->payout_frequency] ?? $this->payout_frequency;
    }
}

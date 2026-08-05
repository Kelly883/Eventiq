<?php

namespace App\Features\Payouts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payout_id',
        'event_id',
        'total_revenue',
        'platform_fee',
        'organizer_share',
        'tax_amount',
        'refund_amount',
        'breakdown',
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'organizer_share' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'breakdown' => 'array',
    ];

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    public function calculateNetPayout(): float
    {
        return (float) $this->organizer_share - (float) $this->refund_amount;
    }

    public function getPlatformFeePercentage(): float
    {
        if ($this->total_revenue <= 0) {
            return 0;
        }
        return ((float) $this->platform_fee / (float) $this->total_revenue) * 100;
    }
}

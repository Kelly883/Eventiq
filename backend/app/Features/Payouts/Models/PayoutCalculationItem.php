<?php

namespace App\Features\Payouts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutCalculationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payout_calculation_id',
        'event_id',
        'order_id',
        'refund_request_id',
        'gross_amount',
        'commission_amount',
        'processing_fee_amount',
        'tax_withholding_amount',
        'net_amount',
        'item_details',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'processing_fee_amount' => 'decimal:2',
        'tax_withholding_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'item_details' => 'array',
    ];

    public function payoutCalculation(): BelongsTo
    {
        return $this->belongsTo(PayoutCalculation::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class);
    }

    public function refundRequest(): BelongsTo
    {
        return $this->belongsTo(\App\Features\Refunds\Models\RefundRequest::class);
    }
}

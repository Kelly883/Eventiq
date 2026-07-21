<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnalyticsSalesTimeline extends Model
{
    use HasFactory;

    protected $table = 'analytics_sales_timeline';

    protected $fillable = [
        'event_id',
        'ticket_tier_id',
        'pricing_window_id',
        'sale_timestamp',
        'quantity',
        'unit_price',
        'total_amount',
        'buyer_email',
        'source',
    ];

    protected $casts = [
        'sale_timestamp' => 'datetime',
        'quantity' => 'integer',
        'unit_price' => 'decimal:10',
        'total_amount' => 'decimal:12',
        'created_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketTier(): BelongsTo
    {
        return $this->belongsTo(TicketTier::class);
    }

    public function pricingWindow(): BelongsTo
    {
        return $this->belongsTo(PricingWindow::class);
    }
}
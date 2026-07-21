<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnalyticsTierPerformance extends Model
{
    use HasFactory;

    protected $table = 'analytics_tier_performance';

    protected $fillable = [
        'event_id',
        'ticket_tier_id',
        'total_sold',
        'total_revenue',
        'average_price',
        'percentage_of_total_sales',
        'percentage_of_total_revenue',
        'conversion_rate',
        'last_updated_at',
    ];

    protected $casts = [
        'total_sold' => 'integer',
        'total_revenue' => 'decimal:12',
        'average_price' => 'decimal:10',
        'percentage_of_total_sales' => 'decimal:5',
        'percentage_of_total_revenue' => 'decimal:5',
        'conversion_rate' => 'decimal:5',
        'last_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketTier(): BelongsTo
    {
        return $this->belongsTo(TicketTier::class);
    }
}
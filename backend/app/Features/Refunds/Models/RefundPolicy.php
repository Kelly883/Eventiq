<?php

namespace App\Features\Refunds\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'organizer_id',
        'refund_window_days',
        'refund_percentage_before_event',
        'refund_percentage_after_event_start',
        'allow_refunds_after_event_start',
        'processing_time_business_days',
        'allowed_refund_methods',
        'requires_approval',
        'auto_approve_threshold',
        'max_refunds_per_user',
        'refund_reasons',
        'cancellation_policy',
        'is_active',
    ];

    protected $casts = [
        'refund_percentage_before_event' => 'decimal:2',
        'refund_percentage_after_event_start' => 'decimal:2',
        'auto_approve_threshold' => 'decimal:2',
        'allow_refunds_after_event_start' => 'boolean',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'allowed_refund_methods' => 'array',
        'refund_reasons' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organizer::class);
    }
}

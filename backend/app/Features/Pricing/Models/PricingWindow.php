<?php

namespace App\Features\Pricing\Models;

use App\Models\Event;
use App\Models\TicketTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PricingWindow extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'event_id',
        'ticket_category_id',
        'window_name',
        'start_date_time',
        'end_date_time',
        'price',
        'quantity_limit',
        'quantity_sold',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'id' => 'string',
        'start_date_time' => 'datetime',
        'end_date_time' => 'datetime',
        'price' => 'decimal:2',
        'quantity_limit' => 'integer',
        'quantity_sold' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function ticketTier(): BelongsTo
    {
        return $this->belongsTo(TicketTier::class, 'ticket_category_id');
    }

    /**
     * Scope: Only currently active windows (is_active = true, within date range, not soft-deleted).
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNull('deleted_at')
            ->where('start_date_time', '<=', now())
            ->where('end_date_time', '>=', now());
    }

    /**
     * Scope: Windows for a specific event.
     */
    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    /**
     * Scope: Windows for a specific ticket tier (category).
     */
    public function scopeForTicketTier($query, $ticketCategoryId)
    {
        return $query->where('ticket_category_id', $ticketCategoryId);
    }

    /**
     * Scope: Windows ordered by priority (highest first), then start date.
     */
    public function scopePrioritized($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('start_date_time');
    }

    /**
     * Check if the window has available capacity.
     */
    public function hasAvailability(): bool
    {
        if ($this->quantity_limit === null) {
            return true;
        }
        return $this->quantity_sold < $this->quantity_limit;
    }

    /**
     * Increment the sold count atomically (for race-safe checkout).
     */
    public function incrementSold(int $quantity = 1): bool
    {
        if (!$this->hasAvailability()) {
            return false;
        }

        return $this->where('id', $this->id)
            ->where('quantity_sold', '<', $this->quantity_limit ?? PHP_INT_MAX)
            ->update(['quantity_sold' => $this->quantity_sold + $quantity]) > 0;
    }
}


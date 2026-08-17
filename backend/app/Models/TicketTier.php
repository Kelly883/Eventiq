<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class TicketTier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'min_purchase',
        'max_purchase',
        'early_bird_price',
        'early_bird_end_date',
        'is_active',
        'quantity',
        'sales_start_date',
        'sales_end_date',
        'benefits_description',
        'tier_image_url',
        'max_per_customer',
        'tier_order',
        'status',
        'currency',
        'voucher_code',
        'sales_channel',
        'published_at',
        'created_by',
        'updated_by',
        'sold_count',
        'is_visible',
        'is_sold_out',
        'allow_repurchase',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'early_bird_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'is_sold_out' => 'boolean',
        'allow_repurchase' => 'boolean',
        'sales_start_date' => 'datetime',
        'sales_end_date' => 'datetime',
        'early_bird_end_date' => 'datetime',
        'published_at' => 'datetime',
        'min_purchase' => 'integer',
        'max_purchase' => 'integer',
        'quantity' => 'integer',
        'max_per_customer' => 'integer',
        'tier_order' => 'integer',
        'sold_count' => 'integer',
    ];

    protected $appends = ['available_count'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getAvailableCountAttribute(): ?int
    {
        if ($this->quantity === null) {
            return null;
        }

        return max(0, $this->quantity - ($this->sold_count ?? 0));
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query, ?\Carbon\Carbon $now = null)
    {
        $now = $now ?: now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('sales_start_date')
                  ->orWhere('sales_start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('sales_end_date')
                  ->orWhere('sales_end_date', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('quantity')
                  ->orWhereRaw('quantity > sold_count');
            });
    }

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('tier_order')->orderBy('sales_start_date');
    }

    public function isEarlyBirdActive(?\Carbon\Carbon $now = null): bool
    {
        $now = $now ?: now();

        return $this->early_bird_price !== null
            && $this->early_bird_end_date !== null
            && $now->isBefore($this->early_bird_end_date);
    }

    public function getEffectivePrice(): float
    {
        return $this->isEarlyBirdActive() ? (float) $this->early_bird_price : (float) $this->price;
    }

    public function isAvailable(?\Carbon\Carbon $now = null): bool
    {
        $now = $now ?: now();

        if ($this->sales_start_date && $now->isBefore($this->sales_start_date)) {
            return false;
        }

        if ($this->sales_end_date && $now->isAfter($this->sales_end_date)) {
            return false;
        }

        return true;
    }

    public function getRemainingQuantity(): ?int
    {
        if ($this->quantity === null) {
            return null;
        }

        return max(0, $this->quantity - ($this->sold_count ?? 0));
    }
}

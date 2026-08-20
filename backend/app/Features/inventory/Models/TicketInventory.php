<?php

namespace App\Features\Inventory\Models;

use App\Models\Event;
use App\Models\TicketTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class TicketInventory extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'ticket_inventory';

    protected $fillable = [
        'event_id',
        'ticket_tier_id',
        'total_allocated',
        'total_sold',
        'low_stock_threshold',
        'last_updated_at',
    ];

    protected $casts = [
        'total_allocated' => 'integer',
        'total_sold' => 'integer',
        'low_stock_threshold' => 'integer',
        'last_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'total_available',
        'is_low_stock',
    ];

    protected $attributes = [
        'low_stock_threshold' => 10,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }

            if ($model->total_sold > $model->total_allocated) {
                throw new \InvalidArgumentException('Total sold cannot exceed total allocated.');
            }
        });

        static::updating(function (self $model) {
            if ($model->total_sold > $model->total_allocated) {
                throw new \InvalidArgumentException('Total sold cannot exceed total allocated.');
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketTier(): BelongsTo
    {
        return $this->belongsTo(TicketTier::class);
    }

    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    public function getTotalAvailableAttribute(): int
    {
        $allocated = (int) ($this->attributes['total_allocated'] ?? 0);
        $sold = (int) ($this->attributes['total_sold'] ?? 0);

        return max(0, $allocated - $sold);
    }

    public function getIsLowStockAttribute(): bool
    {
        $threshold = (int) ($this->attributes['low_stock_threshold'] ?? 0);

        return $this->total_available > 0 && $this->total_available <= $threshold;
    }

    public function updateFromPricingWindows(): void
    {
        $pricingWindows = $this->event->pricingWindows()
            ->where('ticket_category_id', $this->ticket_tier_id)
            ->get();

        $totalAllocated = $pricingWindows->sum('quantity_limit');
        $totalSold = $pricingWindows->sum('quantity_sold');

        $this->update([
            'total_allocated' => $totalAllocated,
            'total_sold' => $totalSold,
            'last_updated_at' => now(),
        ]);
    }

    public static function newFactory()
    {
        return \Database\Factories\TicketInventoryFactory::new();
    }
}

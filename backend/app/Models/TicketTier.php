<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        // 'available_count' is now a virtual/computed column (quantity - sold_count)
        // Do NOT include it in $fillable — it's managed at the DB level
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'early_bird_price' => 'decimal:2',
        'is_active' => 'boolean',
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
        'available_count' => 'integer', // read-only: virtual computed column
    ];

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

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('tier_order')->orderBy('sales_start_date');
    }
}
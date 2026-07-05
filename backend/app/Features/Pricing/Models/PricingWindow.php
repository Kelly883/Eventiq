<?php

namespace App\Features\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PricingWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class);
    }
}

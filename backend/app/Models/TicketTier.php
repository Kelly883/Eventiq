<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'capacity',
        'min_purchase',
        'max_purchase',
        'early_bird_price',
        'early_bird_end_date',
        'benefits',
        'is_active',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}

<?php

namespace App\Features\Delivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryEventData extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'delivery_event_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'delivery_event_id',
        'payload',
        'provider_response',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'provider_response' => 'array',
        'error_message' => 'array',
    ];

    // ── Relationships ────────────────────────────────────────────────

    /**
     * Inverse one-to-one relationship with DeliveryEvent.
     */
    public function deliveryEvent()
    {
        return $this->belongsTo(DeliveryEvent::class);
    }
}

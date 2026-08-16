<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'url',
        'description',
        'secret',
        'subscribed_events',
        'status',
        'last_failure_at',
        'last_success_at',
        'failure_count',
        'timeout_seconds',
        'retry_policy',
    ];

    protected $casts = [
        'subscribed_events' => 'array',
        'retry_policy' => 'array',
        'last_failure_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function deliveryLogs()
    {
        return $this->hasMany(WebhookDeliveryLog::class);
    }
}

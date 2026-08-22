<?php

namespace App\Features\CheckIn\Models;

use App\Models\AuditLog as BaseAuditLog;
use App\Models\Event;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends BaseAuditLog
{
    protected $fillable = [
        'event_id',
        'user_id',
        'action',
        'target_type',
        'target_id',
        'details',
        'status',
        'source',
        'ip_address',
        'geolocation',
        'request_data',
        'response_data',
        'changed_fields',
        'metadata',
        'compliance_classification',
        'retention_date',
        'description',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(\App\Features\Checkout\Models\Ticket::class, 'ticket_id');
    }

    public function scopeByEvent($query, string $eventId)
    {
        return $query->where('event_id', $eventId);
    }
}

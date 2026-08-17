<?php

namespace App\Features\CheckIn\Models;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'event_id',
        'user_id',
        'action',
        'ticket_id',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(\App\Features\Checkout\Models\Ticket::class, 'ticket_id');
    }

    public function scopeByEvent($query, string $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}

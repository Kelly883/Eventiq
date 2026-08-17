<?php

namespace App\Features\CheckIn\Models;

use App\Models\Event;
use App\Models\User;
use App\Features\Checkout\Models\Ticket as CheckoutTicket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';

    protected $fillable = [
        'event_id',
        'ticket_id',
        'attendee_name',
        'attendee_email',
        'tier',
        'status',
        'checked_in_at',
        'checked_in_by',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function fraudEvents(): HasMany
    {
        return $this->hasMany(\App\Features\Fraud\Models\FraudEvent::class, 'ticket_id');
    }

    public function scopeWithFraudEvents($query)
    {
        return $query->with('fraudEvents');
    }

    public function scopeByEvent($query, string $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function isCheckedIn(): bool
    {
        return $this->status === 'checked_in';
    }

    public function canCheckIn(): bool
    {
        if ($this->status !== 'valid') {
            return false;
        }

        return !$this->fraudEvents()
            ->where('fraud_type', 'duplicate_checkin')
            ->exists();
    }

    public function hasDuplicateFraudCheck(): bool
    {
        return $this->fraudEvents()
            ->where('fraud_type', 'duplicate_checkin')
            ->exists();
    }
}

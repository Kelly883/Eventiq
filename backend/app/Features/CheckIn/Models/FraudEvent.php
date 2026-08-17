<?php

namespace App\Features\CheckIn\Models;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudEvent extends Model
{
    use HasFactory;

    protected $table = 'fraud_events';

    protected $fillable = [
        'ticket_id',
        'event_id',
        'fraud_type',
        'detected_at',
        'first_check_in_at',
        'first_check_in_by',
        'second_check_in_at',
        'second_check_in_by',
        'risk_level',
        'notes',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'first_check_in_at' => 'datetime',
        'second_check_in_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function firstCheckInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_check_in_by');
    }

    public function secondCheckInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'second_check_in_by');
    }

    public function isDuplicate(): bool
    {
        return $this->fraud_type === 'duplicate_checkin';
    }
}

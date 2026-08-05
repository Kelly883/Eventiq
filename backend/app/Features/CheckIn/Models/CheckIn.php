<?php

namespace App\Features\CheckIn\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckIn extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'check_ins';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'event_id',
        'scanned_by',
        'status',
        'device_type',
        'device_id',
        'ip_address',
        'user_agent',
        'qr_verified',
        'failure_reason',
        'checked_in_at',
        'client_mutation_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'qr_verified' => 'boolean',
        'checked_in_at' => 'datetime',
    ];

    /**
     * Get the ticket that was checked in.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(\App\Features\Checkout\Models\Ticket::class);
    }

    /**
     * Get the attendee who owns the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the event where the check-in occurred.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    /**
     * Get the staff member or device that performed the scan.
     */
    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}

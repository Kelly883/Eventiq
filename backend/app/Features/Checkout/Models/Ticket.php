<?php

namespace App\Features\Checkout\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'event_id',
        'user_id',
        'ticket_tier_id',
        'ticket_id',
        'attendee_name',
        'attendee_email',
        'tier',
        'status',
        'qr_code_data',
        'qr_code_secret',
        'qr_code_generated_at',
        'qr_code_expires_at',
        'checked_in_at',
        'checked_in_by_uuid',
        'qr_code_scanned_count',
        'last_qr_scan_at',
    ];

    protected $casts = [
        'checked_in' => 'boolean',
        'checked_in_at' => 'datetime',
        'qr_code_generated_at' => 'datetime',
        'qr_code_expires_at' => 'datetime',
        'last_qr_scan_at' => 'datetime',
        'qr_code_scanned_count' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class);
    }

    public function ticketTier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TicketTier::class);
    }

    /**
     * Get the staff member who checked in this ticket.
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'checked_in_by_uuid');
    }
}

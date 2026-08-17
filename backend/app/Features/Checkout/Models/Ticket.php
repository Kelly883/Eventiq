<?php

namespace App\Features\Checkout\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    /**
     * Tickets use UUID primary keys.
     */
    public $incrementing = false;

    /**
     * The primary key is a UUID string, not an auto-increment integer.
     */
    protected $keyType = 'string';

    /**
     * Boot the model to auto-generate UUIDs for new records.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Ticket $ticket) {
            if (empty($ticket->{$ticket->getKeyName()})) {
                $ticket->{$ticket->getKeyName()} = (string) Str::uuid();
            }
        });
    }

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
        'checked_in_by',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the staff member who checked in this ticket.
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'checked_in_by');
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function canBeUsed(): bool
    {
        return $this->isValid() && !$this->isQrExpired() && !$this->isCheckedIn();
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isQrExpired(): bool
    {
        return $this->qr_code_expires_at !== null && now()->greaterThan($this->qr_code_expires_at);
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    public function getQrStatus(): string
    {
        if ($this->isVoid()) {
            return 'void';
        }

        if ($this->isCheckedIn()) {
            return 'checked_in';
        }

        if ($this->isQrExpired()) {
            return 'expired';
        }

        if (in_array($this->status, ['fraud_flagged', 'suspicious'], true)) {
            return 'fraud_flagged';
        }

        return 'valid';
    }

    public function incrementScanCount(): void
    {
        $this->increment('qr_code_scanned_count');
        $this->update(['last_qr_scan_at' => now()]);
    }
}
